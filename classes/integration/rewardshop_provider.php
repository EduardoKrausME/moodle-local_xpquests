<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * classes/integration/rewardshop_provider.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\integration;


use coding_exception;
use moodle_exception;
use ReflectionMethod;

/**
 * Rewardshop provider.
 */
class rewardshop_provider implements credit_provider_interface {
    /**
     * Is available.
     */
    public function is_available(): bool {
        return class_exists('\\local_rewardshop\\api')
            && method_exists('\\local_rewardshop\\api', 'add_credits');
    }

    /**
     * Add.
     */
    public function add(int $userid, int $courseid, int $amount, string $reference): void {
        if ($amount <= 0) {
            return;
        }
        if (!$this->is_available()) {
            throw new moodle_exception('rewardshopapiunavailable', 'local_xpquests');
        }
        $reflection = new ReflectionMethod('\\local_rewardshop\\api', 'add_credits');
        $values = [
            'userid' => $userid,
            'courseid' => $courseid,
            'amount' => $amount,
            'credits' => $amount,
            'source' => 'local_xpquests',
            'component' => 'local_xpquests',
            'reference' => $reference,
            'externalref' => $reference,
            'idempotencykey' => $reference,
            'objectid' => preg_match('/(\\d+)$/', $reference, $m) ? (int)$m[1] : abs((int)crc32($reference)),
            'metadata' => ['reference' => $reference],
            'data' => ['reference' => $reference],
        ];
        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = strtolower($parameter->getName());
            if (array_key_exists($name, $values)) {
                $args[] = $values[$name];
            } else if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else if ($parameter->allowsNull()) {
                $args[] = null;
            } else {
                throw new coding_exception('Unsupported local_rewardshop API parameter: ' . $parameter->getName());
            }
        }
        $reflection->invokeArgs(null, $args);
    }
}
