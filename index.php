<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scoreboard Multi-Note</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --bg: #0f1218; --card: #1e222d; --accent: #00f2ff; --win: #30d158; --lose: #ff453a; }
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: white; margin: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

        /* HEADER */
        header { padding: 15px; background: var(--card); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .group-title { font-weight: 800; color: var(--accent); cursor: pointer; display: flex; align-items: center; gap: 8px; }

        /* SIDEBAR / DRAWER UNTUK DAFTAR CATATAN */
        #drawer { 
            position: fixed; left: -100%; top: 0; width: 80%; height: 100%; 
            background: #161a23; z-index: 100; transition: 0.3s; padding: 20px; 
            box-shadow: 10px 0 20px rgba(0,0,0,0.5);
        }
        #drawer.open { left: 0; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; z-index: 90; }
        .overlay.open { display: block; }

        /* INPUT AREA */
        .input-section { padding: 15px; display: flex; gap: 8px; }
        input { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #333; background: #161a23; color: white; outline: none; }
        button.primary { background: var(--accent); color: black; border: none; padding: 10px 15px; border-radius: 10px; font-weight: bold; }

        /* PLAYER CARDS */
        #playerList { flex: 1; overflow-y: auto; padding: 15px; padding-bottom: 100px; }
        .card { background: var(--card); border-radius: 18px; padding: 15px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .card-top { display: flex; justify-content: space-between; align-items: center; }
        .score { font-size: 32px; font-weight: 800; min-width: 60px; text-align: center; }
        .btn-circle { width: 40px; height: 40px; border-radius: 50%; border: none; cursor: pointer; color: white; font-weight: bold; }

        /* DRAWER CONTENT */
        .drawer-item { padding: 15px; background: #252a36; margin-bottom: 10px; border-radius: 10px; display: flex; justify-content: space-between; }
        .active-note { border: 2px solid var(--accent); }
    </style>
</head>
<body>

    <div id="overlay" class="overlay" onclick="toggleDrawer()"></div>

    <div id="drawer">
        <h3>Daftar Catatan</h3>
        <div class="input-section" style="padding: 0 0 20px 0;">
            <input type="text" id="newNoteName" placeholder="Nama Catatan Baru...">
            <button class="primary" onclick="createNewNote()">BUAT</button>
        </div>
        <div id="noteListContainer"></div>
        <button onclick="toggleDrawer()" style="margin-top: 20px; width: 100%; padding: 10px; background: #444; color: white; border: none; border-radius: 8px;">Tutup</button>
    </div>

    <header>
        <div class="group-title" onclick="toggleDrawer()">
            <i class="fas fa-book"></i> <span id="currentNoteTitle">Catatan 1</span> <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
        </div>
        <div onclick="resetCurrent()"><i class="fas fa-sync-alt" style="color: #666;"></i></div>
    </header>

    <div class="input-section">
        <input type="text" id="playerName" placeholder="Nama Pemain...">
        <button class="primary" onclick="addPlayer()">ADD</button>
    </div>

    <div id="playerList"></div>

<script>
    // Data Structure: { "Catatan 1": [players], "Catatan 2": [players] }
    let allNotes = JSON.parse(localStorage.getItem('MasterNotesDB')) || { "Utama": [] };
    let currentNote = localStorage.getItem('CurrentActiveNote') || "Utama";

    function toggleDrawer() {
        document.getElementById('drawer').classList.toggle('open');
        document.getElementById('overlay').classList.toggle('open');
        renderNoteList();
    }

    function createNewNote() {
        let name = document.getElementById('newNoteName').value.trim();
        if(name && !allNotes[name]) {
            allNotes[name] = [];
            document.getElementById('newNoteName').value = '';
            switchNote(name);
        }
    }

    function switchNote(name) {
        currentNote = name;
        localStorage.setItem('CurrentActiveNote', name);
        toggleDrawer();
        render();
    }

    function renderNoteList() {
        const container = document.getElementById('noteListContainer');
        container.innerHTML = '';
        Object.keys(allNotes).forEach(name => {
            container.innerHTML += `
                <div class="drawer-item ${name === currentNote ? 'active-note' : ''}" onclick="switchNote('${name}')">
                    <span>${name}</span>
                    <i class="fas fa-trash" style="color:#555" onclick="deleteNote(event, '${name}')"></i>
                </div>
            `;
        });
    }

    function deleteNote(e, name) {
        e.stopPropagation();
        if(Object.keys(allNotes).length > 1 && confirm(`Hapus catatan "${name}"?`)) {
            delete allNotes[name];
            if(currentNote === name) currentNote = Object.keys(allNotes)[0];
            saveAll();
            renderNoteList();
            render();
        }
    }

    function addPlayer() {
        let name = document.getElementById('playerName').value.trim();
        if(name) {
            allNotes[currentNote].push({ name, score: 0, bet: 50 });
            document.getElementById('playerName').value = '';
            saveAll();
            render();
        }
    }

    function changeScore(idx, mult) {
        let player = allNotes[currentNote][idx];
        player.score += (player.bet * mult);
        saveAll();
        render();
    }

    function saveAll() {
        localStorage.setItem('MasterNotesDB', JSON.stringify(allNotes));
        localStorage.setItem('CurrentActiveNote', currentNote);
    }

    function render() {
        document.getElementById('currentNoteTitle').innerText = currentNote;
        const list = document.getElementById('playerList');
        list.innerHTML = '';
        
        allNotes[currentNote].forEach((p, i) => {
            let color = p.score > 0 ? 'var(--win)' : (p.score < 0 ? 'var(--lose)' : 'white');
            list.innerHTML += `
                <div class="card">
                    <div class="card-top">
                        <div style="display:flex; flex-direction:column">
                            <span style="font-weight:bold; font-size:18px;">${p.name}</span>
                            <div style="font-size:11px; color:#666; margin-top:5px">
                                BET: <input type="number" value="${p.bet}" onchange="allNotes[currentNote][${i}].bet=parseInt(this.value); saveAll()" 
                                style="width:50px; background:none; border:none; color:var(--accent); font-weight:bold; border-bottom:1px solid #333; padding:0; text-align:center">
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px">
                            <button class="btn-circle" style="background:rgba(255,69,58,0.2); color:var(--lose)" onclick="changeScore(${i},-1)">-</button>
                            <span class="score" style="color:${color}">${p.score}</span>
                            <button class="btn-circle" style="background:rgba(48,209,88,0.2); color:var(--win)" onclick="changeScore(${i},1)">+</button>
                        </div>
                    </div>
                    <div style="text-align:right; margin-top:10px">
                        <button onclick="allNotes[currentNote].splice(${i},1); saveAll(); render()" style="background:none; border:none; color:#444; font-size:10px; text-decoration:underline">Hapus Pemain</button>
                    </div>
                </div>
            `;
        });
    }

    function resetCurrent() {
        if(confirm("Reset skor di catatan ini?")) {
            allNotes[currentNote].forEach(p => p.score = 0);
            saveAll();
            render();
        }
    }

    render();
</script>
</body>
</html>