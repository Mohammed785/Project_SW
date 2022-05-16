USE social;

CREATE TABLE IF NOT EXISTS `users`(
    id int AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    email varchar(150) NOT NULL UNIQUE,
    password varchar(30) NOT NULL,
    profile_photo varchar(150),
    profile_cover varchar(150),
    admin boolean default 0,
    bio TEXT,
    birth_date DATE,
    PRIMARY KEY(id)
);

CREATE TABLE IF NOT EXISTS `posts`(
    id int AUTO_INCREMENT,
    body TEXT NOT NULL,
    user_id int,
    PRIMARY KEY(id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `comments`(
    id int AUTO_INCREMENT,
    body TEXT NOT NULL,
    post_id int NOT NULL,
    user_id int NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `friend_requests`(
    sender_id int NOT NULL,
    requested_id int NOT NULL,
    friends boolean default 1,
    PRIMARY KEY(sender_id,requested_id),
    FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(requested_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `relations`(
    sender_id int NOT NULL,
    requested_id int NOT NULL,
    friends boolean default 1,
    PRIMARY KEY(sender_id,requested_id),
    FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(requested_id) REFERENCES users(id)ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `saved_posts`(
    post_id int NOT NULL,
    user_id int NOT NULL,
    PRIMARY KEY(user_id,post_id),
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id)ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `post_reacts`(
    post_id int NOT NULL,
    user_id int NOT NULL,
    liked boolean NOT NULL,
    PRIMARY KEY(user_id,post_id),
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id)ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `comment_reacts`(
    comment_id int NOT NULL,
    user_id int NOT NULL,
    liked boolean NOT NULL,
    PRIMARY KEY(user_id,comment_id),
    FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `groups`(
    id int AUTO_INCREMENT,
    name varchar(150) NOT NULL,
    description TEXT,
    private boolean DEFAULT 0,
    owner_id int UNIQUE,
    PRIMARY KEY(id),
    FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `group_memberships`(
    user_id int NOT NULL,
    group_id int NOT NULL,
    PRIMARY KEY(user_id,group_id),
    FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `group_requests`(
    user_id int NOT NULL,
    group_id int NOT NULL,
    accepted boolean DEFAULT 0,
    PRIMARY KEY(user_id,group_id),
    FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `reports`(
    creator_id int NOT NULL,
    accused_id int NOT NULL,
    reason TEXT NOT NULL,
    PRIMARY KEY(creator_id,accused_id),
    FOREIGN KEY(creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(accused_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `chats`(
    id int AUTO_INCREMENT,
    user1_id int NOT NULL,
    user2_id int NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY(user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(user2_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `messages`(
    sender_id int NOT NULL,
    chat_id int NOT NULL,
    body TEXT NOT NULL,
    PRIMARY KEY(sender_id,chat_id),
    FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(chat_id) REFERENCES chats(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `storys`(
    id int AUTO_INCREMENT,
    body TEXT NOT NULL,
    author_id int NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `story_views`(
    story_id int NOT NULL,
    user_id int NOT NULL,
    PRIMARY KEY(user_id,story_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(story_id) REFERENCES storys(id) ON DELETE CASCADE
);


