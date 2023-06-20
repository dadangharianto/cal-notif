<?php

// Default timezone
date_default_timezone_set("Asia/Bangkok");

// Perintah looping 
function JalankanBot()
{
    // konfigurasi koneksi
    $host       =  "192.168.12.4";
    $dbuser     =  "uKoneksi";
    $dbpass     =  "sm@rt2018";
    $port       =  "5432";
    $dbname     =  "eqsd";

    // script koneksi php postgree
    $conn = new PDO("pgsql:dbname=$dbname;host=$host", $dbuser, $dbpass);
    // $conn_user = new PDO("pgsql:dbname=$dbname_user;host=$host", $dbuser, $dbpass);

    // script koneksi ke sql server
    // $conntele = new PDO("sqlsrv:Server=192.168.12.5;Database=DBWO", "uKoneksi", "sm@rt2018");
    $conuser = new PDO("sqlsrv:Server=192.168.12.12;Database=SMSGateway", "uKoneksi", "sm@rt2018");

    // Tanggal hari ini 
    $tanggal = date('Y-m-d');

    // Jam hari ini
    $jam = date('H:i:s');

    // Cari data hari ini

    $sql = "SELECT id_setoran, master_id, deskripsi_reminder, dept_abbr, tanggal_tempo , user_terima, tgl_jatuh_tempo FROM internal_memo.vw_im_master_reminder_today";

    $result = $conn->query($sql);

    $new_line = urldecode('%0A');
    echo "-----------------------------------------------------------------------";

    foreach ($result as $tampil) {
        $reminder_id   = $tampil['master_id'];
        // $setoran_id   = $tampil['master_id'];
        $nik_user_terima   = $tampil['user_terima'];
        $message = 'E-MEMO - REMINDER '.$new_line
            .'Dept/Bagian: '.$tampil['dept_abbr'].$new_line
            .'Deskripsi : '.$tampil['deskripsi_reminder'].$new_line
            .'Tanggal Jatuh Tempo : '.date('d-m-Y',strtotime($tampil['tgl_jatuh_tempo']));
        
        echo $new_line;
        echo $message .PHP_EOL;

        //cek berapa kali notif dikirim
        $cek_notif = "SELECT count(*) from internal_memo.tbl_im_master_setoran_log where reminder_id=$reminder_id and tanggal_kirim = '$tanggal'";
        $result_cek = $conn->query($cek_notif);
        foreach ($result_cek as $key) {
            $cek = $key['count'];
        }

        if ($nik_user_terima != ''){

            $get_user_terima = "SELECT dept_abbr, bagian_abbr, nama, nik, telegramid FROM vw_sambupedia_all_pekerja_aktif WHERE nik IN ($nik_user_terima)";
            $user_terima = $conuser->query($get_user_terima);

            foreach ($user_terima as $ut_row) {
            

                        //3X SEHARI NOTIFIKASI
                        if (($cek==0 && $jam >= date('08:00:00') && $jam <= date('12:00:00')) || ($cek==1 && $jam >= date('13:00:00') && $jam <= date('17:00:00'))){
                            // input log
                            $insert_log = "INSERT INTO internal_memo.tbl_im_master_setoran_log (reminder_id,isi_pesan,tanggal_kirim,jam_kirim,telegramid,nama) VALUES('$reminder_id','$message','$tanggal','$jam','$ut_row[telegramid]','$ut_row[nama]')";

                            $result_insert_log = $conn->query($insert_log);

                            if ($result_insert_log) {
                                echo "Log berhasil Disimpan Time:". $jam .PHP_EOL;

                                $sql_tele = "INSERT into TelegramOutbox (DataFrom,ToTelegramID,FirstName,Messages) VALUES('REMINDER','$ut_row[telegramid]','$ut_row[nama]','$message')";
                                $r_telepimp = $conuser->query($sql_tele);
                                if ($r_telepimp){
                                    echo "Succeed send Notification, To ".$tampil['nama']."  Time : ". $jam .PHP_EOL;
                                }else{
                                    echo "Failed send Notification,  To ".$tampil['nama']."  Time : ". $jam .PHP_EOL;
                                }
                            }else{
                                echo "Failed save log ! Time : ". $jam .PHP_EOL;
                                echo "Failed send Notification ! Time : ". $jam .PHP_EOL;
                            }

                        }else{
                            echo "Notifikasi Sudah terkirim ke ".$ut_row['nama']." Time : ". $jam .PHP_EOL;
                        }
                    }
        }else{
            // echo $new_line;
            echo "STATUS : User terima Kosong Time : ". $jam .PHP_EOL;
        }
    }
}

// Jalankan perintah
while (true) {
    // JalankanBot();

    if (date('H:i:s') > date('07:50:00') && date('H:i:s') < date('22:00:00')) {
        JalankanBot();
    }

    // $jeda = 200; // jeda 200 milidetik
    $jeda = 200; // jeda 200 milidetik

    // Detect otomatis, cli atau browser
    if (php_sapi_name() === "cli") {
        sleep($jeda); //beri jeda 2 detik

    } else {
        echo '<meta http-equiv="refresh" content="' . $jeda . '">';
        echo 'Bot sedang jalan-jalan';
        break;
    }
}
