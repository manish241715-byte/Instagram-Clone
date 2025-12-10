<?php
session_start();
if(!isset($_SESSION['username'])){ header("Location: login.php"); exit; }
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// current user id
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $_SESSION['username']); $stmt->execute(); $me = $stmt->get_result()->fetch_assoc(); $stmt->close();
$my_id = $me['id'] ?? 0;

// fetch posts
$sql = "SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC";
$res = $conn->query($sql);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Feed</title>
<style>
.post{border:1px solid #ddd;padding:10px;margin:12px 0;max-width:600px;}
.actions{margin-top:8px;}
.btn{cursor:pointer;border:none;background:none;padding:6px;}
.liked{color:#e0245e;}
</style>
</head>
<body>
<h2>Feed</h2>
<p><a href="create_post.php">Create Post</a> | <a href="logout.php">Logout</a></p>

<div id="posts">
<?php while($post = $res->fetch_assoc()):
    $pid = $post['id'];
    // like count
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM likes WHERE post_id=?"); $stmt->bind_param("i",$pid); $stmt->execute(); $likes = $stmt->get_result()->fetch_assoc()['c']; $stmt->close();
    // user liked?
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=?"); $stmt->bind_param("ii",$pid,$my_id); $stmt->execute(); $liked = $stmt->get_result()->num_rows>0; $stmt->close();
    // comment count
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM comments WHERE post_id=?"); $stmt->bind_param("i",$pid); $stmt->execute(); $cc = $stmt->get_result()->fetch_assoc()['c']; $stmt->close();
?>
  <div class="post" data-post-id="<?php echo $pid ?>">
    <div><strong><?php echo htmlspecialchars($post['username']) ?></strong> <small><?php echo $post['created_at'] ?></small></div>
    <div style="margin-top:6px"><?php echo nl2br(htmlspecialchars($post['content'])) ?></div>
    <?php if(!empty($post['image'])): ?>
      <div><img src="<?php echo htmlspecialchars($post['image']) ?>" style="max-width:100%;margin-top:8px"></div>
    <?php endif; ?>

    <div class="actions">
      <button class="btn like-btn <?php if($liked) echo 'liked' ?>" data-liked="<?php echo $liked?1:0 ?>">♥ <span class="like-count"><?php echo $likes ?></span></button>
      <button class="btn comment-toggle">💬 <span class="comment-count"><?php echo $cc ?></span></button>
      <button class="btn share-btn">↗ Share</button>
    </div>

    <div class="comment-area" style="display:none;margin-top:8px">
      <div class="comment-list"></div>
      <form class="comment-form" style="margin-top:6px">
        <input type="text" name="comment" placeholder="Add comment..." style="width:70%">
        <button type="submit">Send</button>
      </form>
    </div>
  </div>
<?php endwhile; ?>
</div>

<script>
// helper
async function postJSON(url, data){
  const res = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  return res.json();
}

document.querySelectorAll('.post').forEach(postEl=>{
  const postId = postEl.dataset.postId;
  const likeBtn = postEl.querySelector('.like-btn');
  const likeCountEl = postEl.querySelector('.like-count');
  const commentToggle = postEl.querySelector('.comment-toggle');
  const commentArea = postEl.querySelector('.comment-area');
  const commentList = postEl.querySelector('.comment-list');
  const commentForm = postEl.querySelector('.comment-form');

  likeBtn.addEventListener('click', async ()=>{
    const r = await postJSON('like.php',{post_id:postId});
    if(r.success){ likeCountEl.textContent = r.likes; if(r.liked) likeBtn.classList.add('liked'); else likeBtn.classList.remove('liked'); }
    else alert(r.error||'Error');
  });

  commentToggle.addEventListener('click', async ()=>{
    if(commentArea.style.display === 'none'){
      commentArea.style.display = 'block';
      const r = await postJSON('comments_fetch.php',{post_id:postId});
      if(r.success){ commentList.innerHTML = r.comments.map(c=>`<div><strong>${c.username}</strong>: ${c.comment}</div>`).join(''); }
    } else commentArea.style.display = 'none';
  });

  commentForm.addEventListener('submit', async e=>{
    e.preventDefault();
    const text = commentForm.querySelector('input[name="comment"]').value.trim(); if(!text) return;
    const r = await postJSON('comment.php',{post_id:postId,comment:text});
    if(r.success){
      commentList.insertAdjacentHTML('beforeend', `<div><strong>${r.username}</strong>: ${r.comment}</div>`);
      postEl.querySelector('.comment-count').textContent = r.comment_count;
      commentForm.querySelector('input[name="comment"]').value = '';
    } else alert(r.error||'Error');
  });

  postEl.querySelector('.share-btn').addEventListener('click', ()=>{
    const url = location.origin + '/instagram_clone/post_view.php?id=' + postId;
    navigator.clipboard?.writeText(url).then(()=> alert('Link copied'));
  });
});
</script>
</body>
</html>
<?php $conn->close(); ?>
