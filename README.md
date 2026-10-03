# local_xpquests

`local_xpquests` adds personal learning quests to Moodle. Teachers compose a quest from course actions, choose
sequential or any-order progression, define availability dates and optionally make the quest repeatable. Learners see
only their own progress; the plugin deliberately has no leaderboard, class comparison or competitive ranking.

The plugin depends on `local_personalxp` and awards XP only through its public service API. `local_rewardshop`
and `local_xpcelebration` are optional integrations for credits and completion feedback. No table belonging to another
plugin is read or written directly.

Each repeatable execution is stored as a separate progress row. Step completion is unique per execution, while reward
delivery has a separate idempotency ledger. The execution id is propagated as the external reference used by reward
integrations, so duplicated events, reloads and retries do not intentionally award the same reward twice.

Automatic steps are completed only by Moodle events or server-side state checks. The learner-facing UI has no endpoint
capable of asserting completion of an automatic step. Manual steps require `local/xpquests:markmanual`.

Initial step types are activity view, activity completion, quiz attempt, quiz pass, forum post, assignment submission,
course section completion, XP earned and manual confirmation. Additional plugins can
expose `COMPONENT_xpquests_step_types()` from `lib.php` and return a map of type key to class
implementing `\local_xpquests\step_type_interface`.

## Compatibility note

The `local_xpquests` code targets Moodle 4.1 through 4.6 and declares Moodle 4.1 as its own minimum. The
current `local_personalxp` repository, however, declares Moodle 4.5 as its minimum version. Because `local_personalxp`
is a required dependency, the combined installation currently has an effective minimum of Moodle 4.5
unless `local_personalxp` is backported to 4.1-4.4.

## Processing model

Normal learner page loads render persisted quest progress only; they do not scan historical logs, forum posts or quiz
attempts. Moodle events update event-driven steps as actions occur, while the scheduled reconciliation task handles
state-based checks such as XP earned and recovery of pending idempotent rewards. A repeatable quest creates a new
progress execution (`runnumber`) and its step completions and rewards are scoped to that execution.
