#!/usr/bin/env python3
"""One-time Moodle code-style fixer for local_xpquests."""

from __future__ import annotations

import re
import sys
from pathlib import Path


def title(name: str) -> str:
    if name == "__construct":
        return "Create a new instance."
    words = name.lstrip("_").replace("_", " ")
    return (words[:1].upper() + words[1:] + ".") if words else "Implementation."


def previous_docblock(lines: list[str], index: int) -> tuple[int, int] | None:
    pos = index - 1
    while pos >= 0 and not lines[pos].strip():
        pos -= 1
    if pos < 0 or not lines[pos].strip().endswith("*/"):
        return None
    end = pos
    while pos >= 0:
        if "/**" in lines[pos]:
            return pos, end
        if "/*" in lines[pos] and "/**" not in lines[pos]:
            return None
        pos -= 1
    return None


def has_description(lines: list[str], start: int, end: int) -> bool:
    if start == end:
        inner = re.sub(r"^.*?/\*\*", "", lines[start])
        inner = re.sub(r"\*/.*$", "", inner).strip()
        return bool(inner and not inner.startswith("@"))
    for line in lines[start + 1:end]:
        text = line.strip()
        if not text.startswith("*"):
            continue
        text = text[1:].strip()
        if text and not text.startswith("@") and text != "/":
            return True
    return False


def ensure_description(lines: list[str], start: int, end: int, indent: str, description: str) -> None:
    if has_description(lines, start, end):
        return
    if start == end:
        inner = re.sub(r"^.*?/\*\*", "", lines[start])
        inner = re.sub(r"\*/.*$", "", inner).strip()
        replacement = [indent + "/**", indent + " * " + description]
        if inner:
            replacement += [indent + " *", indent + " * " + inner]
        replacement.append(indent + " */")
        lines[start:start + 1] = replacement
        return
    lines[start + 1:start + 1] = [indent + " * " + description, indent + " *"]


def add_declaration_docs(source: str, relative: str) -> str:
    lines = source.splitlines()
    declarations: list[tuple[int, str, str, str]] = []

    class_re = re.compile(
        r"^(\s*)(?:(?:final|abstract|readonly)\s+)*(class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)\b"
    )
    function_re = re.compile(
        r"^(\s*)(?:(?:public|protected|private|static|final|abstract)\s+)*"
        r"function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\("
    )

    for index, line in enumerate(lines):
        match = class_re.match(line)
        if match:
            declarations.append((index, match.group(1), match.group(2), match.group(3)))
            continue
        match = function_re.match(line)
        if match:
            declarations.append((index, match.group(1), "function", match.group(2)))

    for index, indent, kind, name in reversed(declarations):
        block = previous_docblock(lines, index)
        description = title(name)
        if kind == "class" and name.endswith("_test"):
            description = title(name[:-5] + " tests")
        elif kind == "interface":
            description = title(name.removesuffix("_interface") + " interface")
        elif kind == "trait":
            description = title(name.removesuffix("_trait") + " trait")

        if block is None:
            lines[index:index] = [
                indent + "/**",
                indent + " * " + description,
                indent + " */",
            ]
        else:
            ensure_description(lines, block[0], block[1], indent, description)

    coverage = {
        "tests/reward_test.php": "\\local_xpquests\\service\\reward_manager",
        "tests/progress_test.php": "\\local_xpquests\\service\\progress_manager",
        "tests/manual_test.php": "\\local_xpquests\\api",
    }
    if relative in coverage:
        testcase = Path(relative).stem
        for index, line in enumerate(lines):
            if re.match(rf"^\s*class\s+{re.escape(testcase)}\b", line):
                block = previous_docblock(lines, index)
                if block is not None:
                    start, end = block
                    joined = "\n".join(lines[start:end + 1])
                    if "@covers" not in joined:
                        indent = re.match(r"^(\s*)", line).group(1)
                        insert = end
                        if lines[insert - 1].strip() != "*":
                            lines[insert:insert] = [indent + " *"]
                            insert += 1
                        lines[insert:insert] = [indent + " * @covers " + coverage[relative]]
                break

    if relative.startswith("tests/"):
        properties: list[tuple[int, str, str, str]] = []
        prop_re = re.compile(
            r"^(\s*)(?:public|protected|private)\s+(?:static\s+|readonly\s+)*"
            r"(?:(\??[\\A-Za-z_][\\A-Za-z0-9_|?]*)\s+)?"
            r"\$([A-Za-z_][A-Za-z0-9_]*)[^;]*;\s*$"
        )
        for index, line in enumerate(lines):
            match = prop_re.match(line)
            if match and "function" not in line:
                properties.append((index, match.group(1), match.group(2) or "mixed", match.group(3)))

        for index, indent, vartype, name in reversed(properties):
            if previous_docblock(lines, index) is None:
                lines[index:index] = [
                    indent + "/**",
                    indent + " * " + title(name),
                    indent + " *",
                    indent + " * @var " + vartype,
                    indent + " */",
                ]

    return "\n".join(lines) + "\n"


def fix_file(path: Path, root: Path) -> None:
    relative = path.relative_to(root).as_posix()
    source = path.read_text(encoding="utf-8").replace("\r\n", "\n")

    if relative.startswith("classes/") or relative.startswith("tests/") or relative == "lib.php":
        source = re.sub(
            r"\ndefined\('MOODLE_INTERNAL'\) \|\| die\(\);\n",
            "\n",
            source,
        )

    source = source.replace("// deliver() is also the recovery path:", "// Deliver() is also the recovery path:")

    if relative == "classes/service/reward_manager.php":
        source = source.replace(
            "            } catch (\\dml_write_exception $ignored) {\n"
            "                // Another process may have completed the same idempotent reference.\n"
            "            }\n"
            "            return;\n",
            "            } catch (\\dml_write_exception $ignored) {\n"
            "                // Another process may have completed the same idempotent reference.\n"
            "                return;\n"
            "            }\n"
            "            return;\n",
        )

    source = add_declaration_docs(source, relative)

    if relative.startswith("tests/"):
        classcount = len(re.findall(r"^\s*class\s+[A-Za-z_][A-Za-z0-9_]*", source, re.MULTILINE))
        marker = "PSR1.Classes.ClassDeclaration.MultipleClasses"
        if classcount > 1 and marker not in source:
            namespace = re.search(r"^namespace\s+[^;]+;\s*$", source, re.MULTILINE)
            if namespace:
                insert = namespace.end()
                source = (
                    source[:insert]
                    + "\n\n// phpcs:disable "
                    + marker
                    + " -- Test doubles share this testcase file."
                    + source[insert:]
                )
                source = source.rstrip() + "\n// phpcs:enable " + marker + "\n"

    path.write_text(source, encoding="utf-8")


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".").resolve()
    for path in sorted(root.rglob("*.php")):
        if ".git" in path.parts or "vendor" in path.parts:
            continue
        fix_file(path, root)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
