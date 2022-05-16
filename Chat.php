<?php

    include_once "./utils.php";

    class Chat{
        private $id,$user1_id,$user2_id;
        function __construct($chat_info){
            $this->id = isset($chat_info["id"])?$chat_info["id"]:0;
            $this->user1_id = $chat_info["user1_id"];
            $this->user2_id = $chat_info["user2_id"];
            $this->db = getDBConnection();
        }
        
        static function findByID($id){
            $db = getDBConnection();
            $chat = $db->select("SELECT * FROM chats WHERE id={$id}");
            if($chat){
                return $chat[0];
            }else{
                return NULL;
            }
        }
        static function deleteByFriendID($user1_id,$user2_id){
            $db = getDBConnection();
            $deleted = $db->delete("DELETE FROM chats WHERE user1_id={$user1_id} AND user2_id={$user2_id} OR user1_id={$user2_id} AND user2_id={$user1_id}");
            return $deleted;
        }
        static function deleteByID($id){
            $db = getDBConnection();
            $deleted = $db->delete("DELETE FROM chats WHERE id='$id'");
            return $deleted;
        }
    }

?>
