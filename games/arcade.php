<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links
 require_once __DIR__ . '/../includes/navbar.php'; ?>
<?php
 $current_page = 'arcade.php';
?> 

<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Galaxy Memory Math Cards</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/layout.css">
<link rel="stylesheet" href="../assets/games.css">

</head>

<div class="nebula"></div>

<div class="container">
<div class="main-content arcade-page">

<div class="arcade-container">

<!-- HERO -->
<section class="arcade-hero">

<div class="arcade-hero-content">

<div class="arcade-badge">
🪐 GALAXY MEMORY CHALLENGE
</div>

<h1>
Train Your Brain<br>
Across The Universe
</h1>

<p>
Flip glowing cosmic cards, solve math puzzles,
and unlock galaxy levels in this futuristic
memory adventure.
</p>

<div class="arcade-hero-actions">
    <button class="btn primary" onclick="scrollToGame()">▶ Start Mission</button>
</div>

</div>

</section>

<!-- GAME -->
<section class="game-section">

<div class="game-box">

<div class="topbar">

<div class="stats">
<div class="stat">Level: <span id="levelText">EASY</span></div>
<div class="stat">Moves: <span id="moves">0</span></div>
</div>

<div class="buttons">

<div class="size-group" id="sizeGroup">
<button class="size-btn" data-size="small" onclick="changeSize('small')">S</button>
<button class="size-btn active" data-size="medium" onclick="changeSize('medium')">M</button>
<button class="size-btn" data-size="large" onclick="changeSize('large')">L</button>
</div>

<button class="game-btn" onclick="changeLevel('easy')">Easy</button>
<button class="game-btn" onclick="changeLevel('hard')">Hard</button>
<button class="game-btn" onclick="changeLevel('expert')">Expert</button>
<button class="game-btn restart-btn" onclick="restartGame()">Restart</button>
</div>

</div>

<div class="grid" id="grid"></div>

<div class="win" id="winText"></div>

<div class="result" id="result">

<div class="result-grid">

<div class="result-card">
<span>Total Moves</span>
<h2 id="finalMoves">0</h2>
</div>

<div class="result-card">
<span>Completed Level</span>
<h2 id="finalLevel">EASY</h2>
</div>

<div class="result-card">
<span>Performance</span>
<h2 id="performance">Pro</h2>
</div>

</div>
</div>

</div>

</section>

</div>

</div>
</div><!-- /.container -->

<script>

const CARD_ICON = '🪐';

const sizePresets={
small:{height:'85px',font:'1rem',gap:'12px'},
medium:{height:'110px',font:'1.3rem',gap:'16px'},
large:{height:'140px',font:'1.6rem',gap:'20px'}
};

function changeSize(size){
const preset=sizePresets[size];
const scope=document.querySelector('.arcade-page');
scope.style.setProperty('--card-height',preset.height);
scope.style.setProperty('--card-font',preset.font);
scope.style.setProperty('--grid-gap',preset.gap);
document.querySelectorAll('.size-btn').forEach(btn=>{
btn.classList.toggle('active',btn.dataset.size===size);
});
}

const levelData={
easy:[
['2 + 3','5'],
['4 + 4','8'],
['6 - 1','5']
],
hard:[
['3 × 3','9'],
['7 + 6','13'],
['10 - 2','8'],
['5 × 2','10']
],
expert:[
['12 × 2','24'],
['18 ÷ 2','9'],
['14 + 9','23'],
['15 - 7','8'],
['9 × 3','27']
]
};

const levelNumberMap={ easy:1, hard:2, expert:3 };

let currentLevel='easy';
let cards=[];
let flipped=[];
let lockBoard=false;
let moves=0;
let matchedCount=0;

function shuffle(array){
for(let i=array.length-1;i>0;i--){
const j=Math.floor(Math.random()*(i+1));
[array[i],array[j]]=[array[j],array[i]];
}
return array;
}

function buildCards(){
const pairs=levelData[currentLevel];
cards=[];
pairs.forEach((pair,index)=>{
cards.push({ id:index, text:pair[0], type:'q' });
cards.push({ id:index, text:pair[1], type:'a' });
});
shuffle(cards);
}

function renderGrid(){
const grid=document.getElementById('grid');
grid.innerHTML='';
let columns=3;
if(currentLevel==='hard') columns=4;
if(currentLevel==='expert') columns=5;
const boxWidth=grid.parentElement.clientWidth;
const minCardWidth=90;
const maxColumnsThatFit=Math.max(2,Math.floor(boxWidth/minCardWidth));
columns=Math.min(columns,maxColumnsThatFit);
grid.style.gridTemplateColumns=`repeat(${columns},1fr)`;
cards.forEach((card,index)=>{
const div=document.createElement('div');
div.className='card';
div.innerText=CARD_ICON;
div.addEventListener('click',()=>flipCard(div,index));
grid.appendChild(div);
});
}

function flipCard(element,index){
if(lockBoard) return;
if(element.classList.contains('flipped')) return;
if(element.classList.contains('matched')) return;
element.classList.add('flipped');
element.innerText=cards[index].text;
flipped.push({ element, index });
if(flipped.length===2){
lockBoard=true;
moves++;
document.getElementById('moves').innerText=moves;
setTimeout(checkMatch,700);
}
}

function checkMatch(){
const first=flipped[0];
const second=flipped[1];
const firstCard=cards[first.index];
const secondCard=cards[second.index];
const isMatch=firstCard.id===secondCard.id && firstCard.type!==secondCard.type;
if(isMatch){
first.element.classList.add('matched');
second.element.classList.add('matched');
matchedCount++;
if(matchedCount===levelData[currentLevel].length){
document.getElementById('winText').innerText='🎉 Galaxy Cleared!';
showResults();
}
}else{
first.element.classList.remove('flipped');
second.element.classList.remove('flipped');
first.element.innerText=CARD_ICON;
second.element.innerText=CARD_ICON;
}
flipped=[];
lockBoard=false;
}

function showResults(){
document.getElementById('result').style.display='block';
document.getElementById('finalMoves').innerText=moves;
document.getElementById('finalLevel').innerText=currentLevel.toUpperCase();
let rank=(moves<=4)?'Galaxy Master':(moves<=7)?'Pro Explorer':'Rookie';
document.getElementById('performance').innerText=rank;
saveScoreToServer();
}

function movesToScore(movesTaken){
return Math.max(0,1000-(movesTaken*50));
}

function saveScoreToServer(){
fetch('../playgame-api/save_game_progress.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:new URLSearchParams({
game:'mathmatch',
state:JSON.stringify({
moves:moves,
level:currentLevel,
score:movesToScore(moves)
})
})
})
.then(res=>res.json())
.then(data=>{
if(!data.success) console.warn('Score not saved:',data.message);
else console.log('Score synced to database.');
})
.catch(err=>console.error('Score sync failed:',err));
}

function startGame(){
moves=0;
matchedCount=0;
flipped=[];
lockBoard=false;
document.getElementById('moves').innerText='0';
document.getElementById('levelText').innerText=currentLevel.toUpperCase();
document.getElementById('winText').innerText='';
document.getElementById('result').style.display='none';
buildCards();
renderGrid();
}

function changeLevel(level){
currentLevel=level;
startGame();
}

function restartGame(){
startGame();
}

startGame();

window.addEventListener('resize',renderGrid);

const sidebarEl=document.getElementById('sidebar');
if(sidebarEl){
new MutationObserver(renderGrid).observe(sidebarEl,{attributes:true,attributeFilter:['class']});
}

function scrollToGame(){
document.querySelector('.game-section').scrollIntoView({behavior:'smooth'});
}

</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</html>