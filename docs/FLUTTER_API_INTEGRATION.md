# Panduan Integrasi API Presensi Selfie BaknusAttend untuk Flutter

Dokumen ini adalah referensi lengkap untuk tim pengembang aplikasi Flutter (pihak ketiga) agar dapat mengintegrasikan fitur **Login**, **Cek Status Presensi & Radius Geofencing**, serta **Presensi Selfie Wajah (AI CompreFace)**.

---

## 1. Spesifikasi Teknis API

* **Base URL**: `https://<domain-baknusattend>/api`
* **Format Data**: JSON (`Accept: application/json`)
* **Autentikasi**: Bearer Token (`Authorization: Bearer <token>`)
* **Unggah Foto**: `multipart/form-data`

---

## 2. Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/auth/login` | Tidak | Login Siswa / Guru / TU & Mendapatkan Bearer Token |
| `GET` | `/auth/me` | Ya | Cek profil pengguna yang sedang login |
| `GET` | `/presence/status` | Ya | Status presensi hari ini, info radius GPS, & riwayat tap |
| `POST` | `/presence/selfie` | Ya | Submit absen selfie dengan pencocokan wajah & GPS |
| `POST` | `/presence/card-tap` | Ya | Submit absen Tap Kartu NFC smartphone & GPS |
| `GET` | `/presence/bluetooth/challenge` | Ya | Dapatkan kode challenge acak (60s) untuk dikirim ke ESP32 via BLE |
| `POST` | `/presence/bluetooth/verify` | Ya | Verifikasi signature HMAC-SHA256 dari ESP32 & submit presensi |
| `POST` | `/presence/register-face` | Ya | Pendaftaran awal foto master wajah (jika belum ada) |

---

## 3. Rincian Endpoint & Format Payload

### A. Login Pengguna (`POST /auth/login`)
Digunakan oleh Siswa (menggunakan NIS atau Email) dan Guru/TU (menggunakan NIPY atau Email) beserta Password akun.

* **Request Body (JSON)**:
```json
{
  "username": "23241001",
  "password": "password_akun"
}
```
> Catatan: Field `username` bisa diisi NIS (misal `23241001`), NIPY, atau alamat email lengkap (`23241001@smk.baktinusantara666.sch.id`).

* **Response Berhasil (200 OK)**:
```json
{
  "status": "success",
  "message": "Login berhasil.",
  "token": "eyJpdiI6In...<token_panjang>...",
  "expires_at": "2026-10-04T09:15:00+07:00",
  "user": {
    "id": 15,
    "name": "Ahmad Fauzi",
    "email": "23241001@smk.baktinusantara666.sch.id",
    "identifier": "23241001",
    "role": "Siswa",
    "class": "XII PPLG 1",
    "avatar_url": "https://baknusmail.smkbn666.sch.id/api/auth/avatar/23241001@smk.baktinusantara666.sch.id",
    "has_face_master": true,
    "master_photo_url": "https://absen.smkbn666.sch.id/storage/face-references/master_15.jpg"
  }
}
```

---

### B. Cek Status Hari Ini & GPS Sekolah (`GET /presence/status`)
Panggil endpoint ini ketika aplikasi dibuka untuk menentukan kondisi UI:
* Apakah hari ini libur?
* Apakah tombol yang aktif adalah **"Absen Masuk"** atau **"Absen Pulang"**?
* Apakah user berada di dalam radius sekolah?

* **Headers**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`

* **Response Berhasil (200 OK)**:
```json
{
  "status": "success",
  "data": {
    "date": "2026-09-04",
    "day_name": "Jumat",
    "presensi_type": "Masuk", 
    "can_attend": true,
    "reason": null,
    "has_face_master": true,
    "master_photo_url": "https://absen.smkbn666.sch.id/storage/face-references/master_15.jpg",
    "school_setting": {
      "lat": -6.938812,
      "long": 107.721245,
      "radius_meters": 100,
      "is_ip_validation_active": false
    },
    "today_records": [
      {
        "id": 102,
        "waktu_tap": "2026-09-04 06:45:12",
        "jam": "06:45",
        "status": "Hadir",
        "keterangan": "Masuk - Presensi Mandiri (Mobile App)",
        "lat": -6.938810,
        "long": 107.721240,
        "is_dinas_luar": false,
        "lokasi_dinas_luar": null,
        "photo_url": "https://absen.smkbn666.sch.id/storage/absensi-selfie/selfie_102.jpg"
      }
    ]
  }
}
```

> **Nilai `presensi_type`**:
> - `"Masuk"`: User belum absen masuk hari ini.
> - `"Pulang"`: User sudah absen masuk, berikutnya absen pulang.
> - `"Selesai"`: User sudah menyelesaikan absen masuk dan pulang hari ini (`can_attend: false`).
> - `"Libur"`: Hari Minggu atau hari libur nasional (`can_attend: false`).
> - `"Izin"`: Guru/TU memiliki izin/sakit yang aktif (`can_attend: false`).

---

### C. Absen Selfie Wajah (`POST /presence/selfie`)
Mengirim foto selfie dari kamera depan beserta koordinat GPS perangkat.

* **Headers**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`
  - `Content-Type: multipart/form-data`

* **Form-Data Fields**:
  | Key | Tipe | Wajib? | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `photo` | File (image) | **Ya** | File gambar selfie dari kamera (JPG/PNG) |
  | `lat` | Float | **Ya** | Latitude GPS perangkat saat mengambil foto |
  | `long` | Float | **Ya** | Longitude GPS perangkat saat mengambil foto |
  | `is_dinas_luar` | Integer | Tidak | `1` jika dinas luar, `0` jika biasa (default `0`) |
  | `lokasi_dinas_luar` | String | Kondisional | Wajib diisi jika `is_dinas_luar = 1` |

* **Response Sukses (200 OK)**:
```json
{
  "status": "success",
  "message": "Presensi Masuk Berhasil!",
  "data": {
    "id": 105,
    "tipe": "Masuk",
    "status_kehadiran": "Hadir",
    "waktu": "2026-09-04 06:50:20",
    "jam": "06:50",
    "similarity_percent": 95.8,
    "photo_url": "https://absen.smkbn666.sch.id/storage/absensi-selfie/selfie_abc123.jpg",
    "is_dinas_luar": false,
    "lokasi_dinas_luar": null
  }
}
```

* **Response Gagal Verifikasi (422 Unprocessable Content)**:
```json
{
  "status": "error",
  "message": "Wajah selfie tidak cocok dengan foto master (62.3% kecocokan). Harap hadapkan wajah ke kamera dengan pencahayaan cukup.",
  "similarity_percent": 62.3
}
```
*Atau jika diluar radius GPS:*
```json
{
  "status": "error",
  "message": "Anda berada di luar jangkauan absensi sekolah (350m dari sekolah, toleransi 100m).",
  "distance": 350.2,
  "allowed_radius": 100
}
```

---

### D. Presensi Tap Kartu NFC (`POST /presence/card-tap`)
Digunakan jika siswa atau guru/TU melakukan presensi dengan menempelkan kartu fisik (NFC) ke bagian belakang smartphone.

* **Headers**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`
  - `Content-Type: application/json`

* **Request Body (JSON)**:
```json
{
  "rfid_uid": "04A1B2C3D4",
  "lat": -6.938812,
  "long": 107.721245,
  "is_dinas_luar": 0,
  "lokasi_dinas_luar": null
}
```

* **Fitur Cerdas di Server**:
  1. **Anti-Joki**: Jika kartu sudah dimiliki oleh siswa/pegawai lain, request langsung ditolak dengan pesan: *"Kartu ID yang di-tap sudah terdaftar atas nama [Nama Pemilik]"*.
  2. **Auto-Pairing**: Jika akun siswa/pegawai belum memiliki kartu terdaftar di database, kartu yang pertama kali di-tap otomatis disimpan dan ditautkan ke akunnya.
  3. **Validasi Geofencing**: Tetap memeriksa apakah HP berada di dalam radius lingkungan sekolah.

* **Response Sukses (200 OK)**:
```json
{
  "status": "success",
  "message": "Presensi Masuk (Tap NFC) Berhasil!",
  "data": {
    "id": 108,
    "tipe": "Masuk",
    "status_kehadiran": "Hadir",
    "rfid_uid": "04A1B2C3D4",
    "is_newly_linked": false,
    "waktu": "2026-09-04 06:48:15",
    "jam": "06:48",
    "is_dinas_luar": false,
    "lokasi_dinas_luar": null
  }
}
```

---

### E. Presensi Bluetooth BLE Offline Hardware (`Wemos D1 R32 ESP32`)
Metode ini digunakan ketika siswa/guru presensi melalui alat hardware offline ESP32 di lokasi (contoh: gerbang / lorong kelas). HP bertindak sebagai jembatan (bridge) aman menggunakan protokol **Challenge-Response HMAC-SHA256**.

#### Alur Kerja:
1. **Langkah 1**: HP meminta kode challenge acak ke server:
   - **Method**: `GET /presence/bluetooth/challenge`
   - **Headers**: `Authorization: Bearer <token>`
   - **Response**:
     ```json
     {
       "status": "success",
       "challenge_code": "A9F7B2C81D4E5678A9F7B2C81D4E5678",
       "expires_in": 60,
       "user_name": "Ahmad Fauzi",
       "tipe": "Masuk"
     }
     ```
2. **Langkah 2**: HP terhubung ke Wemos ESP32 via Bluetooth Low Energy (BLE), lalu mengirimkan `challenge_code` dan `user_name`.
3. **Langkah 3**: Wemos ESP32 menghitung `signature = HMAC-SHA256(challenge_code, secret_key)`, membunyikan buzzer, menampilkan nama siswa di LCD 16x2, lalu membalas HP dengan `signature` dan `device_id`.
4. **Langkah 4**: HP mengirimkan data verifikasi ke server:
   - **Method**: `POST /presence/bluetooth/verify`
   - **Headers**: `Authorization: Bearer <token>`, `Content-Type: application/json`
   - **Body**:
     ```json
     {
       "device_id": "WEMOS_GERBANG_01",
       "challenge_code": "A9F7B2C81D4E5678A9F7B2C81D4E5678",
       "signature": "c8f9...hasil_hmac_sha256_dari_wemos...",
       "lat": -6.938812,
       "long": 107.721245,
       "is_dinas_luar": 0,
       "lokasi_dinas_luar": null
     }
     ```
   - **Response Sukses (200 OK)**:
     ```json
     {
       "status": "success",
       "message": "Presensi Masuk (Bluetooth BLE) Berhasil!",
       "data": {
         "id": 112,
         "tipe": "Masuk",
         "status_kehadiran": "Hadir",
         "device_id": "WEMOS_GERBANG_01",
         "device_name": "Gerbang Depan",
         "waktu": "2026-09-05 06:47:30",
         "jam": "06:47",
         "is_dinas_luar": false,
         "lokasi_dinas_luar": null
       }
     }
     ```

---

## 4. Contoh Implementasi di Flutter (Dart)

Berikut contoh implementasi service presensi menggunakan package `dio`, `geolocator`, `image_picker`, `nfc_manager`, dan `flutter_blue_plus`.

### Dependencies `pubspec.yaml`
```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.4.0
  geolocator: ^11.0.0
  image_picker: ^1.0.7
  nfc_manager: ^3.3.0
  flutter_blue_plus: ^1.32.0
  flutter_secure_storage: ^9.0.0
```

### Kode Service Presensi (`attendance_service.dart`)
```dart
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:geolocator/geolocator.dart';

class AttendanceService {
  final Dio _dio = Dio(BaseOptions(
    baseUrl: 'https://absen.smkbn666.sch.id/api',
    connectTimeout: const Duration(seconds: 15),
    receiveTimeout: const Duration(seconds: 30),
    headers: {
      'Accept': 'application/json',
    },
  ));

  String? _token;

  void setToken(String token) {
    _token = token;
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  /// 1. Login
  Future<Map<String, dynamic>> login(String username, String password) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'username': username,
        'password': password,
      });

      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final token = response.data['token'];
        setToken(token);
        return response.data;
      }
      throw Exception(response.data['message'] ?? 'Login gagal');
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? e.message);
    }
  }

  /// 2. Ambil Status Hari Ini & Radius Sekolah
  Future<Map<String, dynamic>> getPresenceStatus() async {
    try {
      final response = await _dio.get('/presence/status');
      return response.data['data'];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Gagal mengambil status');
    }
  }

  /// 3. Kirim Foto Selfie & GPS
  Future<Map<String, dynamic>> submitSelfie({
    required File photoFile,
    required double latitude,
    required double longitude,
    bool isDinasLuar = false,
    String? lokasiDinasLuar,
  }) async {
    try {
      final formData = FormData.fromMap({
        'photo': await MultipartFile.fromFile(
          photoFile.path,
          filename: 'selfie_${DateTime.now().millisecondsSinceEpoch}.jpg',
        ),
        'lat': latitude,
        'long': longitude,
        'is_dinas_luar': isDinasLuar ? 1 : 0,
        if (isDinasLuar && lokasiDinasLuar != null)
          'lokasi_dinas_luar': lokasiDinasLuar,
      });

      final response = await _dio.post('/presence/selfie', data: formData);
      return response.data;
    } on DioException catch (e) {
      // Menangkap pesan error spesifik (Wajah tidak cocok, Diluar GPS, dll.)
      final errorMessage = e.response?.data['message'] ?? 'Gagal memproses presensi selfie';
      throw Exception(errorMessage);
    }
  }

  /// 4. Kirim Presensi Tap Kartu NFC & GPS
  Future<Map<String, dynamic>> submitCardTap({
    required String rfidUid,
    required double latitude,
    required double longitude,
    bool isDinasLuar = false,
    String? lokasiDinasLuar,
  }) async {
    try {
      final response = await _dio.post('/presence/card-tap', data: {
        'rfid_uid': rfidUid,
        'lat': latitude,
        'long': longitude,
        'is_dinas_luar': isDinasLuar ? 1 : 0,
        if (isDinasLuar && lokasiDinasLuar != null)
          'lokasi_dinas_luar': lokasiDinasLuar,
      });
      return response.data;
    } on DioException catch (e) {
      final errorMessage = e.response?.data['message'] ?? 'Gagal memproses tap kartu';
      throw Exception(errorMessage);
    }
  }

  /// 5. Request Challenge Token untuk Bluetooth BLE
  Future<Map<String, dynamic>> getBluetoothChallenge() async {
    try {
      final response = await _dio.get('/presence/bluetooth/challenge');
      return response.data;
    } on DioException catch (e) {
      final errorMessage = e.response?.data['message'] ?? 'Gagal meminta challenge token';
      throw Exception(errorMessage);
    }
  }

  /// 6. Verifikasi Signature HMAC-SHA256 dari Wemos via Server
  Future<Map<String, dynamic>> verifyBluetoothAttendance({
    required String deviceId,
    required String challengeCode,
    required String signature,
    double? latitude,
    double? longitude,
    bool isDinasLuar = false,
    String? lokasiDinasLuar,
  }) async {
    try {
      final response = await _dio.post('/presence/bluetooth/verify', data: {
        'device_id': deviceId,
        'challenge_code': challengeCode,
        'signature': signature,
        if (latitude != null) 'lat': latitude,
        if (longitude != null) 'long': longitude,
        'is_dinas_luar': isDinasLuar ? 1 : 0,
        if (isDinasLuar && lokasiDinasLuar != null)
          'lokasi_dinas_luar': lokasiDinasLuar,
      });
      return response.data;
    } on DioException catch (e) {
      final errorMessage = e.response?.data['message'] ?? 'Verifikasi Bluetooth gagal';
      throw Exception(errorMessage);
    }
  }
}
```

---

## 5. Ringkasan Tips untuk UI/UX Flutter

1. **Gunakan Kamera Depan Langsung**:
   Gunakan `ImagePicker().pickImage(source: ImageSource.camera, preferredCameraDevice: CameraDevice.front)`.
2. **Kunci Akurasi GPS**:
   Sebelum membuka kamera, pastikan izin lokasi didapat dan gunakan `Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high)`.
3. **Pesan Error Ramah**:
   Jika server mengembalikan response 422:
   - Apabila kemiripan < 90%, tampilkan dialog: *"Wajah tidak cocok. Pastikan pencahayaan cukup dan wajah menghadap langsung ke kamera."*
   - Apabila diluar radius, tampilkan jarak dan peta ke sekolah.
