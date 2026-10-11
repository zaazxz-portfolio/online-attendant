<?php
function tglIndo($tanggal)
{
    $bulan = [
        1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    
    $ts = strtotime($tanggal);
    $h = $hari[date("w", $ts)];
    $d = date("j", $ts);
    $bln = $bulan[date("n", $ts)];
    $y = date("Y", $ts);
    $jam = date("H:i", $ts);
    
    return "$h, $d $bln $y $jam";
}

function tglIndoShort($tanggal)
{
    $bulan = [
        1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    $ts = strtotime($tanggal);
    $d = date("j", $ts);
    $bln = $bulan[date("n", $ts)];
    $y = date("Y", $ts);
    
    return "$d $bln $y";
}

function distance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

function fotoProfilUrl(?string $value): string
{
    if (empty($value) || $value === 'default.png') {
        return '/public/assets/logo.png';
    }
    if (str_starts_with($value, 'http')) {
        return $value;
    }
    // Jika path sudah ada prefix /public/assets/uploads/ atau /public/uploads/
    if (str_starts_with($value, '/')) {
        return $value;
    }
    $clean = basename($value);
    if (file_exists(__DIR__ . '/../public/assets/uploads/' . $clean)) {
        return '/public/assets/uploads/' . $clean;
    }
    if (file_exists(__DIR__ . '/../public/uploads/' . $clean)) {
        return '/public/uploads/' . $clean;
    }
    return '/public/assets/uploads/' . $clean;
}
?>
