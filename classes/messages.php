<?php
class Messages extends Base{
    protected $tableName            = 'messages';
    protected $hasMessagesTableName = false;
    public    $messages             = array();
    public    $parentField          = 'user_id';
    public    $link                 = '/messages/messages';

    public function __construct(){
        global $Core;

        if(!$Core->userModel){
            throw new Exception("User model is not set");
        }
    }

    public function controlMessages(){
        global $Core;

        if(isset($_REQUEST['hide_notify']) && is_numeric($_REQUEST['hide_notify'])){
            $_REQUEST['hide_notify'] = $Core->db->escape($_REQUEST['hide_notify']);
            $Core->db->query("UPDATE `{$Core->dbName}`.`{$this->tableName}` SET `notify` = 0 WHERE `user_id` = ".$Core->{$Core->userModel}->id." AND `type` = {$_REQUEST['hide_notify']}");
        }elseif(isset($_REQUEST['seen']) && is_numeric($_REQUEST['seen'])){
            $_REQUEST['seen']  = $Core->db->escape($_REQUEST['seen']);

            $Core->db->query("UPDATE `{$Core->dbName}`.`{$this->tableName}` SET `seen` = 1 WHERE `user_id` = ".$Core->{$Core->userModel}->id." AND `id` = {$_REQUEST['seen']}");
        }elseif(isset($_REQUEST['delete'])){
            if(!is_numeric($_REQUEST['delete'])){
                throw new Exception('Invalid message id');
            }
            //delete message
            $this->deleteMessage($_REQUEST['delete']);
        }else{
            //draw messages if new message is present
            if(!isset($_REQUEST['count']) || !is_numeric($_REQUEST['count'])){
                throw new Exception('Provide messages count');
            }

            $_REQUEST['count'] = $Core->db->escape($_REQUEST['count']);
            $this->drawMessages($_REQUEST['count'], true);
        }
        die;
    }

    public function getMessagesCount($userId){
        global $Core;

        //get messages count
        $tableName = $this->tableName;
        $this->changeTableName($this->hasMessagesTableName);
        if($count = $this->getByParentId($userId)){
            $count = current($count)['count'];
        }else{
            $count = false;
        }

        $this->changeTableName($tableName);
        return $count;
    }

    public function getMessages($userId, $limit = false, $type = false){
        global $Core;

        if($type){
            if(!is_array($type)){
                $type = '`type` = '.$type;
            }else{
                $type = '`type` IN ('.(implode(',', $type)).')';
            }
        }

        $this->messages = $this->getByParentId($userId, $limit, $type);
        return $this->messages;

        return false;
    }

    public function drawMessages($check = false, $opened = false, $userId = false){
        global $Core;

        if(!$userId){
            $userId = $Core->{$Core->userModel}->id;
        }

        $count = $this->getMessagesCount($userId);

        if($check !== false && $check == $count){
            //no new messages
            die;
        }

        if($count && ($messages = $this->getMessages($userId))){
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

    public function insertMessage($userId = false, $message, $sender = false, $senderId = false, $type = NULL){
        global $Core;

        if(!$userId){
            $userId = $Core->{$Core->userModel}->id;
        }

        //insert new message
        $this->insert(array('user_id' => $userId, 'message' => $message, 'sender' => $sender, 'sender_id' => $senderId, 'type' => $type));

        $tableName = $this->tableName;
        $this->changeTableName($this->hasMessagesTableName);

        if($current = $this->getByParentId($userId)){
            $current = current($current);
            //update message count
            $count = $current['count'] + 1;
            $this->update($current['id'], array('user_id' => $userId, 'count' => $count));
        }else{
            //insert new row
            $this->insert(array('user_id' => $userId, 'count' => '1'));
            $count = 1;
        }

        $this->changeTableName($tableName);
        return $count;
    }

    public function deleteMessage($id, $userId = false){
        global $Core;

        if(!$userId){
            $userId = $Core->{$Core->userModel}->id;
        }

        //delete if message id exists for user id
        if($this->delete($id, $this->parentField.' = '.$userId)){
            //update message count
            $tableName = $this->tableName;
            $this->changeTableName($this->hasMessagesTableName);

            $current = $this->getByParentId($userId);
            $current = current($current);
            $count   = $current['count'] - 1;

            if($count >= 0){
                $this->update($current['id'], array('count' => $count));
            }

            $this->changeTableName($tableName);
            return $count;
        }
        return false;
    }
}
?>