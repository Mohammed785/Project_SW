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
    function getMyPosts(){
        $posts = $this->db->select("SELECT * FROM posts WHERE user_id={$this->id}");
        return $posts;
    }
    
    function getMySavedPosts(){
        $posts = $this->db->select("SELECT * FROM posts JOIN saved_posts sv WHERE sv.post_id=posts.id AND sv.user_id = {$this->id}");
        return $posts;
    }

    function getFriendsPosts(){
        $posts = $this->db->select("SELECT p.*
                    FROM posts p
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

    function savePost($post_id){
        if(!$this->id){
            $this->save();
        }
        if($this->db->select("SELECT * FROM saved_posts WHERE post_id={$post_id} AND user_id={$this->id}")){
            return false;
        }
        return $this->db->insert("INSERT INTO saved_posts(post_id,user_id) VALUES({$post_id},{$this->id})");
    }

    function commentOnPost($post_id,$comment){
        return $this->db->insert("INSERT INTO comments(body,post_id,user_id) VALUES({$comment},{$post_id},{$this->id})");
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
        return $friendship;
    }

    function deleteFriendship($friend_id){
        if(!$this->getFriendship($friend_id)){
            echo "Users Are Not Friends!!";
            return false;
        }
        $deleted = $this->db->delete("DELETE FROM relations WHERE sender_id={$friend_id} AND requested_id={$this->id} AND friends=1 OR 
        sender_id={$this->id} AND requested_id={$friend_id} AND friends=1");
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


    function viewStory($story_id){
        $exists = $this->db->select("SELECT * FROM story_views WHERE story_id={$story_id} AND user_id={$this->id}");
        if($exists){
            return false;
        }
        return $this->db->insert("INSERT INTO story_views(story_id,user_id) VALUES({$story_id},{$this->id})");
    }

    /**Groups Queries Function Section */
    function joinGroup($group_id){
        $member =$this->db->select("SELECT * from group_memberships WHERE group_id={$group_id} AND user_id={$this->id}");
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
        $member =$this->db->select("SELECT * from group_memberships WHERE group_id={$group_id} AND user_id={$this->id}");
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


    /**Global Queries Function Section */

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



