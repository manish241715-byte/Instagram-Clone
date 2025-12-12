
//to change the post or caption
SELECT id, content, image FROM posts;
//to change name 
SELECT id, username FROM users;

SET FOREIGN_KEY_CHECKS = 0;


DELETE FROM comments;
DELETE FROM likes;
DELETE FROM stories;
DELETE FROM posts;
DELETE FROM users;


INSERT INTO users (id, username, profile_pic) VALUES
(1, 'Er.Manish Subedi', 'subedi.jpg'),
(2, 'Er.Ghale', 'ghale.jpg'),
(3, 'Er.Pratik', 'pratik.jpg'),
(4, 'Mr.Prakash', 'prakash.jpg'),
(5, 'Er.Bhupendra', 'bhupendra.jpg');


INSERT INTO posts (id, user_id, content, image) VALUES
(1, 1, 'Manish coding 💻', 'post1.jpg'),
(2, 2, 'Ghale rock messi ⚡️', 'post2.jpg'),
(3, 3, 'Pratik tiktok star ⭐️', 'post3.jpg'),
(4, 4, 'Prakash superhero 🦸', 'post4.jpg'),
(5, 5, 'Bhupendra new project 🚀', 'post5.jpg');


INSERT INTO stories (id, user_id, media) VALUES
(1, 1, 'subedi_story.jpg'),
(2, 2, 'ghale_story.jpg'),
(3, 3, 'pratik_story.jpg'),
(4, 4, 'prakash_story.jpg'),
(5, 5, 'bhupendra_story.jpg');

SET FOREIGN_KEY_CHECKS = 1;
//this is for profile
DROP TABLE IF EXISTS `followers`;

CREATE TABLE `followers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,    -- the user who is following someone
    `follow_id` INT NOT NULL   -- the user being followed
);

INSERT INTO `followers` (`user_id`, `follow_id`) VALUES
(2,1),  -- user 2 follows you
(3,1),  -- user 3 follows you
(1,4),  -- you follow user 4
(1,5);  -- you follow user 5
