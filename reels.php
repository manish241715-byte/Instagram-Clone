<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Redirect if not logged in
if(!isset($_SESSION['username'])){
    exit('Not logged in');
}

// ===== FETCH REELS =====
$reels_videos = ['reel1.mp4','reel2.mp4','reel3.mp4','reel4.mp4','reel5.mp4'];
?>

<div id="reelsContainer">
<?php
foreach($reels_videos as $video){
    echo '<div class="reel">';
    echo '<video src="reels/'.$video.'" muted autoplay loop playsinline></video>'; 
    echo '<div class="reel-buttons">';
    echo '<button class="reel-like-btn">♡</button>';
    echo '<button class="reel-comment-btn"><i class="fa-regular fa-comment"></i></button>';
    echo '<button class="reel-share-btn"><i class="fa-solid fa-paper-plane"></i></button>';
    echo '</div></div>';
}
?>
</div>

<script>
// ===== Play only the visible reel video =====
const reelVideos = document.querySelectorAll('#reelsContainer video');
function playVisibleReel(){
    reelVideos.forEach(v => v.pause());
    for(let v of reelVideos){
        const rect = v.getBoundingClientRect();
        if(rect.top >= 0 && rect.bottom <= window.innerHeight){
            v.play();
            break;
        }
    }
}

// Initial play
playVisibleReel();

// Play on scroll
document.getElementById('reelsContainer').addEventListener('scroll', playVisibleReel);
</script>