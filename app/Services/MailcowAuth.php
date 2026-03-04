<?php

namespace App\Services;

class MailcowAuth
{
    public static function check($email, $password)
    {
        $host = env('MAILCOW_URL');
        $host = str_replace(['http://', 'https://'], '', $host);

        // Membatasi waktu tunggu (timeout) maksimal 4 detik agar tidak loading lama
        imap_timeout(IMAP_OPENTIMEOUT, 4);

        // Gunakan parameter /novalidate-cert untuk mempercepat SSL Handshake
        // Coba port 993 (IMAPS) terlebih dahulu
        $mbox = @imap_open("{{$host}:993/imap/ssl/novalidate-cert}", $email, $password, OP_HALFOPEN, 1);

        if ($mbox) {
            imap_close($mbox);
            return true;
        }

        // Membatasi waktu tunggu (timeout) kedua 
        imap_timeout(IMAP_OPENTIMEOUT, 3);

        // Coba port standar 143 (TLS) jika 993 gagal/ditutup
        $mbox = @imap_open("{{$host}:143/imap/tls/novalidate-cert}", $email, $password, OP_HALFOPEN, 1);

        if ($mbox) {
            imap_close($mbox);
            return true;
        }

        return false;
    }
}
