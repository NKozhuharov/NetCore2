<?php
class Messages extends Base
{
    protected $tableName   = 'messages';
    public    $parentField = 'user_id';
    public    $link        = '/messages/messages';

    /**
     * @throws Error
     */
    public function __construct() {
        global $Core;

        if (!$Core->userModel) {
            throw new Error("User model is not set");
        }
    }

    /**
     * Controls messages actions
     * @throws Error
     */
    public function controlMessages() {
        global $Core;

        if (isset($_REQUEST['hide_notify']) && is_numeric($_REQUEST['hide_notify'])) {
            $Core->db->query("
                UPDATE `{$Core->dbName}`.`{$this->tableName}`
                SET `notify` = 0
                WHERE `user_id` = ".$Core->{$Core->userModel}->id."
                AND `type` = {$Core->db->escape($_REQUEST['hide_notify'])}"
            );
        } elseif (isset($_REQUEST['seen']) && is_numeric($_REQUEST['seen'])) {
            $Core->db->query("
                UPDATE `{$Core->dbName}`.`{$this->tableName}`
                SET `seen` = 1
                WHERE `user_id` = ".$Core->{$Core->userModel}->id."
                AND `id` = {$Core->db->escape($_REQUEST['seen'])}"
            );
        } elseif (isset($_REQUEST['delete'])) {
            if (!is_numeric($_REQUEST['delete'])) {
                throw new Error('Invalid message id');
            }
            //delete message
            $this->deleteMessage($_REQUEST['delete']);
        } else {
            //draw messages if new message is present
            if (!isset($_REQUEST['count']) || !is_numeric($_REQUEST['count'])) {
                throw new Error('Provide messages count');
            }

            $this->drawMessages(NULL, intval($_REQUEST['count']), true);
        }
        die;
    }

    /**
     * Returns the user messages
     * If type is present it will return the messages only with this type
     * @param int $userId
     * @param int $limit - messages limit
     * @param int $type - messages type
     * @returns array
     */
    public function getMessages(int $userId = NULL, int $limit = 0, int $type = NULL) {
        global $Core;

        if (!$userId) {
            $userId = $Core->{$Core->userModel}->id;
        }

        $additional = " {$this->parentField} = {$userId}";

        if ($type) {
            if (!is_array($type)) {
                $additional .= ' AND `type` = '.$type;
            } else {
                $additional .= ' AND `type` IN ('.(implode(',', $type)).')';
            }
        }

        return $this->getAll($limit, $additional);
    }

    /**
     * Draws messages html
     * @param int $userId
     * @param int $check - current messages count shown to user
     * @param bool $opened - should the messages list be opened
     */
    public function drawMessages(int $userId = NULL, int $check = 0, bool $opened = false) {
        global $Core;

        if (!$userId) {
            $userId = $Core->{$Core->userModel}->id;
        }

        $messages = $this->getMessages($userId);
        $count = count($messages);

        if ($count == $check) {
            die;
        }

        if ($count) {
        ?>
            <div class="messages-counter"><?php echo $count; ?></div>

            <div class="messages <?php echo !$opened ? 'closed' : ''; ?>">
                <div class="messages-in">
                    <?php foreach($messages as $m){?>
                        <div class="message">
                            <i data-href="<?php echo $this->link.'?delete='.$m['id']; ?>" class="message-delete glyphicon glyphicon-remove-circle"></i>

                            <div class="message-text">
                                <?php echo $m['message']; ?>
                            </div>

                            <div class="message-info">
                                <div class="message-arrow"></div>
                                <div class="message-date">
                                    <?php echo date('d-m-Y H:i', strtotime($m['added'])); ?>
                                </div>
                                <div class="message-sender">
                                    <i class="glyphicon glyphicon-user"></i> <?php echo $m['sender']; ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php
        }
    }

    /**
     * Inserts new message
     * @param int $userId
     * @param string $message - message content
     * @param string $sender - name of the sender
     * @param int $senderId - id of the sender
     * @param int $type - type of the message
     * @returns int
     */
    public function insertMessage(int $userId = NULL, string $message = NULL, string $sender = NULL, int $senderId = NULL, int $type = NULL) {
        global $Core;

        if (!$userId) {
            $userId = $Core->{$Core->userModel}->id;
        }

        return $this->insert(array('user_id' => $userId, 'message' => $message, 'sender' => $sender, 'sender_id' => $senderId, 'type' => $type));
    }

    /**
     * Deletes a message by given id
     * @param int $id - mesage id
     * @param int $userId - the user id of the message
     * @returns int
     */
    public function deleteMessage(int $id, int $userId = NULL) {
        global $Core;

        if (!$userId) {
            $userId = $Core->{$Core->userModel}->id;
        }

        return $this->deleteById($id, $this->parentField.' = '.$userId);
    }
}
