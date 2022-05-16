<?php

include_once "./baseEntity.php";
include_once "./utils.php";

class User implements Entity{
    private $id,$admin;
    public $name,$email,$password,$profile_photo,$profile_cover,$bio,$birth_date;

    function __construct($userInfo){
        $this->id = isset($userInfo["id"])?$userInfo["id"]:0;
        $this->password = $userInfo["password"];
        $this->name = $userInfo["name"];
        $this->email = $userInfo["email"];
        $this->admin = $userInfo["admin"];
        $this->profile_photo = $userInfo["profile_photo"];
        $this->profile_cover = $userInfo["profile_cover"];
        $this->birth_date = $userInfo["birth_date"];
        $this->bio = $userInfo["bio"];
        $this->db = getDBConnection();
    }

    function getId(){
        return $this->id;
    }
    function isAdmin(){
        return $this->admin;
    }
    function save(){
        if($this->id==0){
            return $this->db->insert("INSERT INTO users(name,email,password) VALUES('$this->name','$this->email','$this->password')");
        }
        return $this->update();
    }
    function update(){
        if($this->id!=0){
            return $this->db->update("UPDATE users SET name = '$this->name',email='$this->email',password='$this->password' WHERE id='$this->id'");

        }
        return $this->save();
    }
    function delete(){
        if($this->id!=0){
            return $this->db->delete("DELETE * FROM users WHERE id='$this->id'");
        }
        echo "Cannot Delete. Record Is Not Saved In DB.";
        return false;
    }

    
    /* Post Queries Section */
    function getPost($post_id){
        $post = $this->db->select("SELECT * FROM posts WHERE id={$post_id}");
        return $post;
    }
    function getMyPosts(){
        $posts = $this->db->select("SELECT * FROM posts WHERE user_id={$this->id}");
        return $posts;
    }
    
    function getFriendsPosts(){
        $posts = $this->db->select("SELECT p.* , u.*
                    FROM posts p
                    JOIN users u ON u.id=p.user_id
                    WHERE
                    p.user_id IN (SELECT requested_id FROM relations
                    WHERE sender_id = {$this->id} AND friends=1
                    UNION ALL
                    SELECT sender_id FROM relations
                    WHERE requested_id = {$this->id} AND friends=1);"
        );
        shuffle($posts);
        return $posts;
    }
    
    function createPost($body){
        if(!$this->id){
            $this->save();
        }
        $post = $this->db->insert("INSERT INTO posts(body,user_id) VALUES({$body},{$this->id})");
        return $post;
    }
    function deletePost($post_id){
        $exists = $this->getPost($post_id);
        if($exists){
            if($exists[0]["user_id"]!=$this->id){
                return false;
            }
        }
        $deleted = $this->db->delete("DELETE FROM posts WHERE id={$post_id}");
        return $deleted;
    }
    function updatePost($newBody,$post_id){
        $exists = $this->getPost($post_id);
        if(!$exists || $exists[0]["user_id"]!=$this->id){
            return false;
        }
        $updated = $this->db->update("UPDATE posts SET body={$newBody} WHERE id={$post_id}");
        return $updated;
    }
    function getMySavedPosts(){
        $posts = $this->db->select("SELECT p.id as post_id,p.body FROM saved_posts sv 
        JOIN posts p WHERE sv.post_id=p.id AND sv.user_id ={$this->id}");
        return $posts;
    }

    function savePost($post_id){
        if(!$this->id){
            $this->save();
        }
        if($this->db->select("SELECT * FROM saved_posts WHERE post_id={$post_id} AND user_id={$this->id}")){
            return false;
        }
        return $this->db->insert("INSERT INTO saved_posts(post_id,user_id) VALUES({$post_id},{$this->id})");
    }
    function unSavePost($post_id){
        if(!$this->id){
            $this->save();
        }
        return $this->db->delete("DELETE FROM saved_posts(post_id,user_id) VALUES({$post_id},{$this->id})");
    }

    function deleteAllMyPosts(){
        if(!$this->id){
            $this->save();   
        }
        return $this->db->delete("DELETE FROM posts WHERE user_id={$this->id}");
    }

    function commentOnPost($post_id,$comment){
        if(!$this->id){
            $this->save();   
        }
        return $this->db->insert("INSERT INTO comments(body,post_id,user_id) VALUES({$comment},{$post_id},{$this->id})");
    }

    function updateComment($newBody,$comment_id){
        $exists = $this->db->select("SELECT * FROM comments WHERE id={$comment_id}");
        if(!$exists || $exists[0]["user_id"]!=$this->id){
            return false;
        }
        $updated = $this->db->update("UPDATE comments SET body={$newBody} WHERE id={$comment_id}");
        return $updated;
    }

    function deleteComment($comment_id){
        if(!$this->id){
            $this->save();   
        }
        $comment = $this->db->select("SELECT * FROM comments WHERE id={$comment_id}");
        if($comment["user_id"]==$this->id){
            return $this->db->delete("DELETE FROM comments WHERE id={$comment_id}");
        }
        $post_id = $comment["post_id"];
        $post = $this->db->select("SELECT * FROM posts WHERE id={$post_id} AND user_id={$this->user}");
        if($post){
            if($post["user_id"]==$this->id){
                return $this->db->delete("DELETE FROM comments WHERE id={$comment_id}");
            }
        }
        return false;
    }

    function deleteAllMyCommentsOnPost($post_id){
        if(!$this->id){
            $this->save();   
        }
        return $this->db->delete("DELETE FROM comments WHERE user_id={$this->id} AND post_id={$post_id}");
    }

    function deleteAllMyComments(){
        if(!$this->id){
            $this->save();   
        }
        return $this->db->delete("DELETE FROM comments WHERE user_id={$this->id}");
    }

    /**FriendRequest Queries Section */
    function getFriendRequests($friend_id=NULL,$reverse=false){
        if($friend_id && !$reverse){
            $request = $this->db->select("SELECT * FROM friend_requests WHERE sender_id={$this->id} AND requested_id={$friend_id}");
            return $request;
        }elseif($reverse){
            $request = $this->db->select("SELECT * FROM friend_requests WHERE sender_id={$friend_id} AND requested_id={$this->id}");
            return $request;
        }
        $requests = $this->db->select("SELECT * FROM friend_requests WHERE requested_id={$this->id}");
        return $requests;
    }

    function sendFriendshipRequest($friend_id){
        if(!$this->id){
            $this->save();
        }
        if($this->getFriendRequests($friend_id)){
            return false;
        }elseif($this->getFriendRequests($friend_id,true)) {
            return $this->createFriendship($friend_id);
        }elseif($this->getBlocks($friend_id)){
            return false;
        }
        return $this->db->insert("INSERT INTO friend_requests(sender_id,requested_id) VALUES({$this->id},{$friend_id})");

    }

    function acceptFriendshipRequest($friend_id){
        if(!$this->id){
            $this->save();
        }
        if(!$this->getFriendRequests($friend_id)){
            return false;
        }
        $request = $this->db->update("UPDATE friend_requests SET accepted=1 WHERE sender_id={$friend_id} AND requested_id = {$this->id}");
        if(!$request){
            return false;
        }
        return $this->createFriendship($friend_id);
    }


    /*FriendShip Queries Functions Section*/


    /**if you want certain friendship pass id of the friend
     * if you want all user friends don't pass anything
     */
    function getFriendship($friend_id=NULL){
        if($friend_id){
            $friend = $this->db->select("SELECT * FROM relations WHERE sender_id={$this->id} AND requested_id={$friend_id} AND friends=1");
            // return $friend[1] to return the friend data
            return $friend;
        }
        $friends = $this->db->select("SELECT * FROM relations WHERE sender_id={$this->id} AND friends = 1");
        return $friends;
    }

    function createFriendship($friend_id){
        if(!$this->id){
            $this->save();
        }
        if($exists = $this->getFriendship($friend_id)){
            echo "Users Are Already Friends";
            return $exists;
        }elseif($this->getBlocks($friend_id)){
            $this->unBlockUser($friend_id);
        }
        $friendship = $this->db->insert("INSERT INTO relations(sender_id,requested_id,friends) 
        VALUES({$this->id},{$friend_id},1)"
        );
        $reverse_friendship = $this->db->insert("INSERT INTO relations(sender_id,requested_id,friends)
        VALUES({$friend_id},{$this->id},1)"
        );
        $this->createChat($friend_id);
        return $friendship;
    }

    function deleteFriendship($friend_id){
        if(!$this->getFriendship($friend_id)){
            echo "Users Are Not Friends!!";
            return false;
        }
        $deleted = $this->db->delete("DELETE FROM relations WHERE sender_id={$friend_id} AND requested_id={$this->id} AND friends=1 OR 
        sender_id={$this->id} AND requested_id={$friend_id} AND friends=1");
        $this->deleteChat($friend_id);
        return $deleted;
    }

    /**BlockRelation Queries Section */
    function getBlocks($blocked_id=NULL){
        if($blocked_id){
            $blocked = $this->db->select("SELECT * FROM relations WHERE sender_id={$this->id} AND requested_id={$blocked_id} AND friends=0");
            // return $blocked[1] to return the blocked user data
            return $blocked;
        }
        $blocked_users = $this->db->select("SELECT * FROM relations WHERE sender_id={$this->id} AND friends = 0");
        return $blocked_users;
    }
    function blockUser($user_id){
        if($this->getFriendship($user_id)){
            $this->deleteFriendship($user_id);
        }elseif($this->getBlocks($user_id)){
            return false;
        }
        $block = $this->db->insert("INSERT INTO relations(sender_id,requested_id,friends) 
        VALUES({$this->id},{$user_id},0)"
        );
        $reverse_block = $this->db->insert("INSERT INTO relations(sender_id,requested_id,friends)
        VALUES({$user_id},{$this->id},0)"
        );
        return $block;
    }
    function unBlockUser($user_id){
        if(!$this->getBlocks($user_id)){
            echo "User Are Not Blocked!!";
            return false;
        }
        $deleted = $this->db->delete("DELETE FROM relations WHERE sender_id={$user_id} AND requested_id={$this->id} AND friends=0 OR 
        sender_id={$this->id} AND requested_id={$user_id} AND friends=0");
        return $deleted;
    }

    /**Story Function Section */
    function createStory($body){
        return $this->db->insert("INSERT INTO storys(body,author_id) VALUES({$body},{$this->id})");
    }
    function deleteStory($story_id){
        return $this->db->delete("DELETE FROM storys WHERE story_id={$story_id}");
    }
    function viewStory($story_id){
        $exists = $this->db->select("SELECT * FROM story_views WHERE story_id={$story_id} AND user_id={$this->id}");
        if($exists){
            return false;
        }
        return $this->db->insert("INSERT INTO story_views(story_id,user_id) VALUES({$story_id},{$this->id})");
    }

    /**Groups Queries Function Section */

    function checkGroupMembership($group_id){
        $member =$this->db->select("SELECT * from group_memberships WHERE group_id={$group_id} AND user_id={$this->id}");
        if($member){
            return true;
        }
        return false;
    }

    function joinGroup($group_id){
        $member =$this->checkGroupMembership($group_id);
        if($member){
            return false;
        }
        $group = $this->db->select("SELECT * from groups WHERE id={$group_id}");
        if($group["private"]){
            return $this->db->insert("INSERT INTO group_requests(user_id,group_id) VALUES({$this->id},{$group_id})");
        }
        return $this->db->insert("INSERT INTO group_memberships(user_id,group_id) VALUES({$this->id},{$group_id})");
    }
    function leaveGroup($group_id){
        $member = $this->checkGroupMembership($group_id);
        if(!$member){
            return false;
        }
        return $this->db->delete("DELETE * FROM group_memberships WHERE group_id={$group_id} AND user_id={$this->id}");
    }
    function acceptGroupRequest($user_id,$group_id){
        $group = $this->db->select("SELECT * from groups WHERE id={$group_id}");
        if($group["owner_id"]!=$this->id){
            return false;
        }
        $request=$this->db->select("SELECT * from group_requests WHERE group_id={$group_id} AND user_id={$user_id}");
        if($request["accepted"]){
            return false;
        }
        $this->db->update("UPDATE group_requests SET accepted=1 WHERE group_id={$group_id} AND user_id={$user_id}");
        return $this->db->insert("INSERT INTO group_memberships(user_id,group_id) VALUES({$user_id},{$group_id})");
    }
    function refuseGroupRequest($user_id,$group_id){
        $group = $this->db->select("SELECT * from groups WHERE id={$group_id}");
        if($group["owner_id"]!=$this->id){
            return false;
        }
        $request=$this->db->select("SELECT * from group_requests WHERE group_id={$group_id} AND user_id={$user_id}");
        if($request["accepted"]){
            return false;
        }
        return $this->db->delete("DELETE * FROM group_requests WHERE group_id={$group_id} AND user_id={$user_id}");
    }
    function viewGroupRequest($group_id){
        $group = $this->db->select("SELECT * from groups WHERE id={$group_id}");
        if($group["owner_id"]!=$this->id){
            return false;
        }
        $requests=$this->db->select("SELECT * from group_requests WHERE group_id={$group_id}");
        return $requests;
    }

    /**Reports Function Section */

    function createReport($reason,$user_id){
        return $this->db->insert("INSERT INTO reports(reason,creator_id,accused_id)VALUES({$reason},{$this->id},{$user_id})");
    }
    function getReports(){
        if(!$this->admin){
            return false;
        }
        return $this->db->select("SELECT r.reason,u.id as `c_id`,u.name `c_name`,u.profile_photo `c_pic`,
        u2.id `a_id`,u2.name `a_name`,u2.profile_photo `a_pic` 
        FROM reports r JOIN users u ON r.creator_id=u.id JOIN users u2 ON r.accused_id = u2.id;");
    }
    function acceptReport($creator_id,$accused_id){
        if(!$this->admin){
            return false;
        }
        return $this->db->delete("DELETE FROM reports WHERE creator_id={$creator_id} AND accused_id={$accused_id}");
    }
    function cancelReport($creator_id,$accused_id){
        if(!$this->admin){
            return false;
        }
        return $this->db->delete("DELETE FROM reports WHERE creator_id={$creator_id} AND accused_id={$accused_id}");
    }

    /**Chat Queries Function Section  */

    function getChatWithChatID($chat_id){
        $chat = $this->db->select("SELECT * FROM chats WHERE id={$chat_id}");
        return $chat;
    }
    function getChatWithFriendID($friend_id){
        $chat=$this->db->select("SELECT * FROM chats WHERE user1_id={$this->id} AND user2_id={$friend_id} OR user1_id={$friend_id} AND user2_id={$this->id}");
        return $chat;
    }
    function getChatMSG($chat_id){
        $messages = $this->db->select("SELECT * FROM messages WHERE chat_id={$chat_id}");
        return $messages;
    }

    private function createChat($friend_id){
        $chat = $this->db->insert("INSERT INTO chats(user1_id,user2_id) VALUES({$this->id},{$friend_id})");
        return $chat;
    }
    private function deleteChat($friend_id){
        $chat = $this->db->delete("DELETE FROM chats WHERE user1_id={$this->id} AND user2_id={$friend_id} OR user1_id={$friend_id} AND user2_id={$this->id}");
        return $chat;
    }

    function sendMessage($chat_id,$body){
        $msg = $this->db->insert("INSERT INTO messages(body,sender_id,chat_id) VALUES({$body},{$this->id},{$chat_id})");
        return $msg;
    }

    /**Global Queries Function Section */

    /**help you with the login process */
    static function authenticate($email,$password){
        $user  = User::findByEmail($email);
        if(!$user || $user->password!=$password){
            return false;
        }
        return $user;
    }

    static function findByEmail($email){
        $db = getDBConnection();
        $user = $db->select("SELECT * FROM users WHERE email='{$email}'");
        if($user){
            return new User($user[0]);
        }
        return NULL;
    }

    static function findByID($id){
        $db = getDBConnection();
        $user = $db->select("SELECT * FROM users WHERE id={$id}");
        if($user){
            return new User($user[0]);
        }else{
            return NULL;
        }
    }

    /**criteria format 
     * array -> array("name = 'test'","email = 'test@test.com'")
     * string -> "name = 'test' AND email = 'test@test.com'"
     */
    static function findAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $users_data = $db->select("SELECT * FROM users WHERE {$criteria}");
        $users = array();
        foreach ($users_data as $user) {
            array_push($users,new User($user));
        }
        return $users;
    }

    static function deleteByID($id){
        $db = getDBConnection();
        $deleted = $db->delete("DELETE FROM users WHERE id='$id'");
        return $deleted;
    }

    static function deleteAll($criteria){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $deleted = $db->delete("DELETE FROM users WHERE {$criteria}");
        return $deleted;
    }

    static function updateAll($criteria,$newData){
        $db = getDBConnection();
        $criteria = createCriteria($criteria);
        $newData  = createCriteria($newData,",");
        $updated = $db->update("UPDATE users SET {$newData} WHERE {$criteria}");
        return $updated;
    }

    static function fromArray($data){
        $users = array();
        foreach($data as $user){
            array_push($users,new User($user));
        }
        return $users;
    }
}
?>

