<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/service/reward_manager.php for local_xpquests.
 *
 * @package    local_xpquests
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpquests\service;

defined('MOODLE_INTERNAL') || die();

class reward_manager {
    /** @var \local_xpquests\integration\xp_provider_interface */
    private $xp;
    /** @var \local_xpquests\integration\credit_provider_interface */
    private $credits;

    public function __construct(
        ?\local_xpquests\integration\xp_provider_interface $xp = null,
        ?\local_xpquests\integration\credit_provider_interface $credits = null
    ) {
        $this->xp = $xp ?: new \local_xpquests\integration\personalxp_provider();
        $this->credits = $credits ?: new \local_xpquests\integration\rewardshop_provider();
    }

    public function deliver(\stdClass $quest, \stdClass $progress): void {
        if ((int)$quest->rewardxp > 0) {
            $this->deliver_one($quest, $progress, 'xp', (int)$quest->rewardxp);
        }
        if ((int)$quest->rewardcredits > 0) {
            if ($this->credits->is_available()) {
                $this->deliver_one($quest, $progress, 'credits', (int)$quest->rewardcredits);
            } else {
                $this->ensure_unavailable_record($progress, 'credits', (int)$quest->rewardcredits);
            }
        }
    }

    public function retry(\stdClass $quest, \stdClass $progress): void {
        // deliver() is also the recovery path: it creates missing ledger rows and
        // skips rows that were already delivered.
        $this->deliver($quest, $progress);
    }

    private function deliver_one(\stdClass $quest, \stdClass $progress, string $type, int $amount): void {
        global $DB;
        $factory = \core\lock\lock_config::get_lock_factory('local_xpquests');
        $lock = $factory->get_lock('reward_' . $progress->id . '_' . $type, 10);
        if (!$lock) {
            throw new \moodle_exception('rewardlockfailed', 'local_xpquests');
        }

        $reference = 'local_xpquests:' . $type . ':' . $progress->id;
        try {
            $record = $DB->get_record('local_xpquests_rewards', [
                'progressid' => $progress->id,
                'rewardtype' => $type,
            ]);
            if ($record && $record->status === 'delivered') {
                return;
            }

            $transaction = null;
            try {
                $transaction = $DB->start_delegated_transaction();
                $now = time();
                if (!$record) {
                    $record = (object)[
                        'progressid' => $progress->id,
                        'rewardtype' => $type,
                        'amount' => $amount,
                        'status' => 'pending',
                        'externalref' => $reference,
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ];
                    $record->id = $DB->insert_record('local_xpquests_rewards', $record);
                } else {
                    $record->status = 'pending';
                    $record->amount = $amount;
                    $record->externalref = $reference;
                    $record->timemodified = $now;
                    $DB->update_record('local_xpquests_rewards', $record);
                }

                if ($type === 'xp') {
                    $this->xp->award((int)$progress->userid, (int)$quest->courseid, $amount, $reference);
                } else {
                    $this->credits->add((int)$progress->userid, (int)$quest->courseid, $amount, $reference);
                }

                $record->status = 'delivered';
                $record->timemodified = time();
                $DB->update_record('local_xpquests_rewards', $record);
                $transaction->allow_commit();
                $transaction = null;
            } catch (\Throwable $e) {
                if ($transaction !== null) {
                    try {
                        $transaction->rollback($e);
                    } catch (\Throwable $rolledback) {
                        $e = $rolledback;
                    }
                }
                $this->mark_failed($progress, $type, $amount, $reference);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    private function mark_failed(\stdClass $progress, string $type, int $amount, string $reference): void {
        global $DB;
        $record = $DB->get_record('local_xpquests_rewards', [
            'progressid' => $progress->id,
            'rewardtype' => $type,
        ]);
        $now = time();
        if (!$record) {
            $record = (object)[
                'progressid' => $progress->id,
                'rewardtype' => $type,
                'amount' => $amount,
                'status' => 'failed',
                'externalref' => $reference,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            try {
                $DB->insert_record('local_xpquests_rewards', $record);
            } catch (\dml_write_exception $ignored) {
                // Another process may have completed the same idempotent reference.
            }
            return;
        }
        if ($record->status !== 'delivered') {
            $record->status = 'failed';
            $record->timemodified = $now;
            $DB->update_record('local_xpquests_rewards', $record);
        }
    }

    private function ensure_unavailable_record(\stdClass $progress, string $type, int $amount): void {
        global $DB;
        if ($DB->record_exists('local_xpquests_rewards', [
            'progressid' => $progress->id,
            'rewardtype' => $type,
        ])) {
            return;
        }
        $now = time();
        $DB->insert_record('local_xpquests_rewards', (object)[
            'progressid' => $progress->id,
            'rewardtype' => $type,
            'amount' => $amount,
            'status' => 'unavailable',
            'externalref' => 'local_xpquests:' . $type . ':' . $progress->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
