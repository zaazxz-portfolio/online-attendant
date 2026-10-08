let stream;
const video = document.getElementById("camera");
const canvas = document.getElementById("canvas");

navigator.mediaDevices.getUserMedia({ video: true })
.then(s => video.srcObject = s);

let lat = null, lng = null;

navigator.geolocation.getCurrentPosition(pos => {
    lat = pos.coords.latitude;
    lng = pos.coords.longitude;
    locText.innerText = lat.toFixed(5) + ", " + lng.toFixed(5);
});

captureBtn.onclick = () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);

    let foto = canvas.toDataURL("image/jpeg");
    uploadAbsensi(foto);
};

async function uploadAbsensi(foto) {
    let user = JSON.parse(localStorage.getItem("user"));
    let tipe = localStorage.getItem("tipe_absen");

    let fd = new FormData();
    fd.append("user_id", user.id);
    fd.append("foto", foto);
    fd.append("latitude", lat);
    fd.append("longitude", lng);
    fd.append("tipe", tipe);

    let res = await fetch("api/absen.php", { method: "POST", body: fd });
    let json = await res.json();

    alert(json.msg);

    setTimeout(() => {
        window.location = "home.html";
    }, 1500);
}
