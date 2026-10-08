// LOGIN
document.getElementById("loginForm")?.addEventListener("submit", async e => {
    e.preventDefault();
    
    let u = username.value;
    let p = password.value;

    let res = await fetch("api/login.php", {
        method: "POST",
        body: new FormData(loginForm)
    });

    let json = await res.json();

    if (json.success) {
        localStorage.setItem("user", JSON.stringify(json.user));
        window.location = "home.html";
    } else {
        alert("Login gagal!");
    }
});

// DASHBOARD LOAD
if (location.pathname.includes("home.html")) {
    let user = JSON.parse(localStorage.getItem("user"));

    nama.innerText = user.nama;
    fakultas.innerText = "Fakultas: " + user.fakultas;
    tahun.innerText = "Tahun Masuk: " + user.tahun;
    lokasi.innerText = "Lokasi: " + user.lokasi;

    loadRiwayat();
    loadStatus();
}

async function loadRiwayat() {
    let uid = JSON.parse(localStorage.getItem("user")).id;
    let res = await fetch("api/list_absen.php?user_id=" + uid);
    let data = await res.json();

    riwayatList.innerHTML = data.map(d =>
        `<li>${d.tipe} - ${d.status_lokasi} (${d.created_at})</li>`
    ).join("");
}

async function loadStatus() {
    let uid = JSON.parse(localStorage.getItem("user")).id;
    let res = await fetch("api/status.php?user_id=" + uid);
    let data = await res.json();

    statusHariIni.innerText = data.status;
}

function absen(tipe) {
    localStorage.setItem("tipe_absen", tipe);
    window.location = "absen.html";
}
