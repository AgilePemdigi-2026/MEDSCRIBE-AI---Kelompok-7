# SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK (SRS) - MEDSCRIBE AI
## Modul Behavior-Driven Development (BDD)

---

### E. User Story

- **US-01 (Autentikasi Tenaga Medis):** As a healthcare professional (Doctor or Nurse), I want to log in to the MedScribe AI system so that I can access electronic medical records securely according to my clinical role.
- **US-02 (Keluar Sistem / Logout):** As an authenticated healthcare professional, I want to log out from the MedScribe AI system so that patient data privacy is protected after my shift ends.
- **US-03 (Registrasi Akun Medis):** As a new healthcare professional, I want to register an account with my affiliated hospital and verify my email via OTP so that I can join the clinical workspace with a verified identity.
- **US-04 (Pemulihan Kata Sandi):** As a healthcare professional, I want to request a password reset OTP to my email and set a new password so that I can regain access if I forget my password.
- **US-05 (Isolasi Data Rumah Sakit):** As a healthcare professional, I want to only view and process patient records from my designated hospital so that patient medical privacy and health regulations are strictly adhered to.
- **US-06 (Manajemen Antrean & TTV Perawat):** As a clinic nurse at the Nurse Station, I want to register new patients and record comprehensive vital signs (TTV) so that the doctor receives accurate objective patient data before consultation.
- **US-07 (Konsultasi & AI Medical Scribe Dokter):** As an outpatient doctor, I want to review the patient's pre-filled vital signs, record consultation audio, and review the medical record so that the AI can assist in clinical SOAP documentation.

---

### F. User Story Scenario (Specification)

#### 1. Autentikasi Tenaga Medis (Login & Logout)

| Login & Logout |
| :--- |
| **Feature:** Autentikasi Tenaga Medis (Login & Logout)<br>**In order to** access electronic medical records securely according to my clinical role<br>**As a** healthcare professional (Doctor or Nurse)<br>**I want to** log in and log out of the MedScribe AI system |

```gherkin
Scenario: Doctor Login Success
  Given I am on the "/login" page
  And I fill in "Email" with "rina@medscribe.id"
  And I fill in "Password" with "password123"
  When I press "Masuk"
  Then I should be redirected to "/dokter/dashboard"
  And I should see "dr. Rina Anggraini, Sp.PD"
  And I should see "Dokter Poliklinik"

Scenario: Nurse Login Success
  Given I am on the "/login" page
  And I fill in "Email" with "dewi@medscribe.id"
  And I fill in "Password" with "password123"
  When I press "Masuk"
  Then I should be redirected to "/perawat/dashboard"
  And I should see "Dewi Pratiwi"
  And I should see "Perawat"

Scenario: Failed Login (missing fields)
  Given I am on the "/login" page
  And I fill in "Email" with ""
  And I fill in "Password" with ""
  When I press "Masuk"
  Then I should see the message "Email wajib diisi."
  And I should remain on the "/login" page

Scenario: Failed Login (email not registered)
  Given I am on the "/login" page
  And I fill in "Email" with "unknown.doctor@medscribe.id"
  And I fill in "Password" with "password123"
  When I press "Masuk"
  Then I should see the message "Kredensial yang diberikan tidak cocok dengan data kami."
  And I should remain on the "/login" page

Scenario: Failed Login (wrong password)
  Given I am on the "/login" page
  And I fill in "Email" with "rina@medscribe.id"
  And I fill in "Password" with "wrongpassword999"
  When I press "Masuk"
  Then I should see the message "Kredensial yang diberikan tidak cocok dengan data kami."
  And I should remain on the "/login" page

Scenario: Failed Login (account pending approval)
  Given I am on the "/login" page
  And I fill in "Email" with "pending.nurse@medscribe.id"
  And I fill in "Password" with "password123"
  When I press "Masuk"
  Then I should see the message "Akun Anda sedang menunggu persetujuan administrator rumah sakit."
  And I should remain on the "/login" page

Scenario: Authenticated User Redirected From Login Page
  Given I am logged in as a doctor
  When I go to the "/login" page
  Then I should be redirected to "/dashboard"

Scenario: Logout Success
  Given I am logged in as a doctor
  And I am on the "/dokter/dashboard" page
  When I press "Keluar dari Sistem" button
  Then I should see the modal "modalLogout"
  When I press "Keluar" button in the modal
  Then I should be redirected to "/login"
  And I should see "Masuk ke Akun MedScribe AI"
```

---

#### 2. Registrasi Akun Tenaga Medis dan Verifikasi OTP Email

| Register & Email OTP Verification |
| :--- |
| **Feature:** Registrasi Akun Tenaga Medis dan Verifikasi OTP Email<br>**In order to** join the hospital clinical workspace with a verified identity<br>**As a** new healthcare professional<br>**I want to** register an account and verify the OTP code sent to my email |

```gherkin
Scenario: Register Success
  Given I am on the "/register" page
  And I fill in "Nama Lengkap" with "dr. Siti Rahma"
  And I fill in "Email" with "siti.rahma@medscribe.id"
  And I select "Dokter" from the "Role" dropdown
  And I select "RS Universitas Airlangga" from the "Rumah Sakit" dropdown
  And I fill in "Password" with "Rahasia@2026"
  And I fill in "Konfirmasi Password" with "Rahasia@2026"
  When I press "Daftar Sekarang"
  Then I should be redirected to "/verify-email-otp"
  And I should see "Verifikasi Email Anda"
  And I should see the message "Kode OTP telah dikirimkan ke email Anda"

Scenario: Failed Register (missing fields)
  Given I am on the "/register" page
  And I fill in "Nama Lengkap" with ""
  And I fill in "Email" with ""
  And I fill in "Password" with ""
  And I fill in "Konfirmasi Password" with ""
  When I press "Daftar Sekarang"
  Then I should see the message "Nama lengkap wajib diisi."
  And I should remain on the "/register" page

Scenario: Failed Register (passwords do not match)
  Given I am on the "/register" page
  And I fill in "Nama Lengkap" with "dr. Budi Setiawan"
  And I fill in "Email" with "budi.setiawan@medscribe.id"
  And I select "Dokter" from the "Role" dropdown
  And I select "RS Universitas Airlangga" from the "Rumah Sakit" dropdown
  And I fill in "Password" with "Password@123"
  And I fill in "Konfirmasi Password" with "PasswordBeda@456"
  When I press "Daftar Sekarang"
  Then I should see the message "Konfirmasi password tidak cocok."
  And I should remain on the "/register" page

Scenario: Failed Register (email already registered)
  Given I am on the "/register" page
  And I fill in "Nama Lengkap" with "dr. Rina Anggraini"
  And I fill in "Email" with "rina@medscribe.id"
  And I select "Dokter" from the "Role" dropdown
  And I select "RS Universitas Airlangga" from the "Rumah Sakit" dropdown
  And I fill in "Password" with "password123"
  And I fill in "Konfirmasi Password" with "password123"
  When I press "Daftar Sekarang"
  Then I should see the message "Email ini sudah terdaftar."
  And I should remain on the "/register" page

Scenario: Verify Email OTP Success
  Given I am on the "/verify-email-otp" page
  And I fill in "Kode OTP" with "654321"
  When I press "Verifikasi Akun"
  Then I should be redirected to "/login"
  And I should see the message "Email Anda berhasil diverifikasi. Silakan masuk."

Scenario: Failed Verify Email OTP (wrong code)
  Given I am on the "/verify-email-otp" page
  And I fill in "Kode OTP" with "000000"
  When I press "Verifikasi Akun"
  Then I should see the message "Kode verifikasi tidak valid atau telah kedaluwarsa."
  And I should remain on the "/verify-email-otp" page

Scenario: Resend Email OTP Success
  Given I am on the "/verify-email-otp" page
  When I press "Kirim Ulang Kode"
  Then I should see the message "Kode verifikasi baru telah dikirimkan ke email Anda."
  And I should remain on the "/verify-email-otp" page
```

---

#### 3. Lupa dan Reset Password via OTP

| Forgot & Reset Password |
| :--- |
| **Feature:** Lupa dan Reset Password via OTP<br>**In order to** regain access to my medical account if I forget my password<br>**As a** healthcare professional<br>**I want to** request a password reset OTP and set a new secure password |

```gherkin
Scenario: Request Password Reset Success
  Given I am on the "/forgot-password" page
  And I fill in "Email" with "dewi@medscribe.id"
  When I press "Kirim Kode Verifikasi"
  Then I should be redirected to "/forgot-password/verify-otp"
  And I should see "Verifikasi Kode OTP Reset Password"

Scenario: Verify Password Reset OTP Success
  Given I am on the "/forgot-password/verify-otp" page
  And I fill in "Kode OTP" with "789123"
  When I press "Verifikasi OTP"
  Then I should be redirected to "/reset-password"
  And I should see "Atur Ulang Kata Sandi"

Scenario: Failed Verify Password Reset OTP (wrong code)
  Given I am on the "/forgot-password/verify-otp" page
  And I fill in "Kode OTP" with "111111"
  When I press "Verifikasi OTP"
  Then I should see the message "Kode verifikasi tidak valid atau telah kedaluwarsa."
  And I should remain on the "/forgot-password/verify-otp" page

Scenario: Reset Password Success
  Given I am on the "/reset-password" page
  And I fill in "Kata Sandi Baru" with "PasswordBaru#2026"
  And I fill in "Konfirmasi Kata Sandi" with "PasswordBaru#2026"
  When I press "Simpan Password Baru"
  Then I should be redirected to "/login"
  And I should see the message "Password berhasil diperbarui. Silakan login menggunakan password baru Anda."

Scenario: Failed Reset Password (passwords do not match)
  Given I am on the "/reset-password" page
  And I fill in "Kata Sandi Baru" with "PasswordBaru#2026"
  And I fill in "Konfirmasi Kata Sandi" with "PasswordBeda#999"
  When I press "Simpan Password Baru"
  Then I should see the message "Konfirmasi password tidak cocok."
  And I should remain on the "/reset-password" page
```

---

#### 4. Isolasi Data Multi-Tenancy Berbasis Rumah Sakit

| Hospital Multi-Tenancy Isolation |
| :--- |
| **Feature:** Isolasi Data Multi-Tenancy Berbasis Rumah Sakit<br>**In order to** protect patient medical privacy and adhere to healthcare data regulations<br>**As a** healthcare professional<br>**I want to** only access clinical data belonging to my assigned hospital |

```gherkin
Scenario: Nurse Only Sees Patients from Assigned Hospital
  Given I am logged in as a nurse assigned to "RS Universitas Airlangga"
  When I go to the "/perawat/dashboard" page
  Then I should see patients registered at "RS Universitas Airlangga"
  And I should not see patients registered at "RSUD Dr. Soetomo"

Scenario: Doctor Blocked from Accessing Other Hospital Records
  Given I am logged in as a doctor assigned to "RS Universitas Airlangga"
  When I go to the medical record URL of a patient from "RSUD Dr. Soetomo"
  Then I should see an error page with status "403 Forbidden"
  And I should see "Akses ditolak: Hanya dokter poliklinik rumah sakit terkait yang berwenang membuka rekam medis."

Scenario: Doctor Blocked from Uploading Audio for Other Hospital Patient
  Given I am logged in as a doctor assigned to "RS Universitas Airlangga"
  When I attempt to upload consultation audio to a patient from "RSUD Dr. Soetomo"
  Then I should receive an error response with status "403 Forbidden"

Scenario: Doctor Blocked from Streaming Audio of Other Hospital Patient
  Given I am logged in as a doctor assigned to "RS Universitas Airlangga"
  When I attempt to stream audio of a patient from "RSUD Dr. Soetomo"
  Then I should receive an error response with status "403 Forbidden"

Scenario: Nurse Blocked from Cancelling Other Hospital Patient
  Given I am logged in as a nurse assigned to "RS Universitas Airlangga"
  When I attempt to cancel a patient registered at "RSUD Dr. Soetomo"
  Then I should receive an error response with status "403 Forbidden"
```

---

#### 5. Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV

| Nurse Station - Patient Queue & Vital Signs |
| :--- |
| **Feature:** Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV<br>**In order to** prepare and record objective patient vital signs for outpatient consultation<br>**As a** clinic nurse<br>**I want to** manage the queue, register new patients, and record vital signs (TTV) |

```gherkin
Scenario: Register New Patient and Initial Vital Signs Success
  Given I am logged in as a nurse
  And I am on the "/perawat/dashboard" page
  When I press "Registrasi Pasien Baru" button
  Then I should see the modal "modalInputPasien"
  When I fill in "Nama Lengkap Pasien" with "Andi Prasetyo"
  And I fill in "Umur" with "35"
  And I select "Laki-laki" from the "Jenis Kelamin" dropdown
  And I fill in "Tekanan Darah Sistolik" with "120"
  And I fill in "Tekanan Darah Diastolik" with "80"
  And I fill in "Denyut Nadi" with "80"
  And I fill in "Suhu Tubuh" with "36.6"
  And I fill in "Laju Pernapasan" with "18"
  And I fill in "Saturasi Oksigen" with "99"
  And I press "Simpan Data Pasien & TTV"
  Then I should be redirected to "/perawat/dashboard?tab=pasien"
  And I should see "Andi Prasetyo"
  And I should see the message "Pasien berhasil didaftarkan"

Scenario: Search Patient in Queue
  Given I am logged in as a nurse
  And I am on the "/perawat/dashboard" page
  When I fill in "Cari Pasien" with "Budi Santoso"
  Then I should see "Budi Santoso"
  And I should not see "Siti Aminah"

Scenario: Record Vital Signs (TTV) for Waiting Patient
  Given I am logged in as a nurse
  And I am on the "/perawat/dashboard" page
  And I select a patient with status "Menunggu TTV" from the queue
  When I fill in "Tekanan Darah" with "120/80"
  And I fill in "Denyut Nadi" with "82"
  And I fill in "Suhu Tubuh" with "36.8"
  And I fill in "Laju Pernapasan" with "18"
  And I fill in "Berat Badan" with "68"
  And I fill in "Tinggi Badan" with "170"
  And I fill in "Keluhan / Catatan" with "Pasien mengeluh pusing sejak kemarin"
  And I press "Simpan Pemeriksaan Awal"
  Then I should see the message "Pemeriksaan awal berhasil disimpan"
  And I should see the queue status updated to "Siap Periksa"

Scenario: Visual High Fever Warning Indicator
  Given I am logged in as a nurse
  And I am on the "/perawat/dashboard" page
  When I fill in "Suhu Tubuh" with "38.8"
  Then I should see the warning badge "Demam Tinggi"
  And I should see text "Suhu tubuh > 37.5°C"

Scenario: Cancel Patient Registration
  Given I am logged in as a nurse
  And I am on the "/perawat/dashboard?tab=pasien" page
  When I press "Batalkan Pemeriksaan" button on a patient
  Then I should see the modal "modalBatalPasien"
  When I fill in "Alasan Pembatalan" with "Pasien pulang sebelum diperiksa"
  And I press "Konfirmasi Pembatalan"
  Then I should be redirected to "/perawat/dashboard?tab=pasien"
  And I should see the message "Registrasi pasien berhasil dibatalkan"
  And I should see the status marked as "Dibatalkan"
```

---

#### 6. Poliklinik Dokter - Rekam Medis dan AI Medical Scribe

| Doctor Consultation & Medical Records |
| :--- |
| **Feature:** Poliklinik Dokter - Rekam Medis dan AI Medical Scribe<br>**In order to** conduct outpatient consultations and record patient clinical findings<br>**As a** clinic doctor<br>**I want to** review patient vital signs, record consultation audio, and review the medical record |

```gherkin
Scenario: Open Patient Medical Record
  Given I am logged in as a doctor
  And I am on the "/dokter/dashboard" page
  When I press "Buka Rekam Medis" on a patient with status "Siap Periksa"
  Then I should be redirected to the medical record page
  And I should see "TTV & Catatan Perawat"
  And I should see "Rekam Wawancara Klinis"
  And I should see the consultation status "Sedang Diperiksa"

Scenario: Review Pre-filled Vital Signs from Nurse
  Given I am logged in as a doctor
  When I am on the medical record page of a patient who has completed TTV
  Then I should see the vital signs "125/85"
  And I should see the temperature "37.2"
  And I should see the nurse's clinical note

Scenario: Upload Consultation Audio Recording Success
  Given I am logged in as a doctor
  And I am on the medical record page
  When I attach the audio file "konsultasi.wav" to the recording uploader
  And I press "Unggah Rekaman"
  Then I should see the message "Rekaman wawancara klinis berhasil disimpan di server."
  And I should see the recording status "Tersimpan"

Scenario: Failed Upload Audio (no file attached)
  Given I am logged in as a doctor
  And I am on the medical record page
  When I press "Unggah Rekaman" without selecting a file
  Then I should see the error message "Berkas audio wajib diunggah."

Scenario: Stream Consultation Audio Recording
  Given I am logged in as a doctor
  And I am on the medical record page of a patient with a saved recording
  When I press the audio player play button
  Then I should hear the consultation audio playback
```

---

### G. Behavior Driven Development (BDD) - Hasil Pengujian

#### a. Login & Logout
| Scenario | Status |
| :--- | :---: |
| Doctor Login Success | **Passed** |
| Nurse Login Success | **Passed** |
| Failed Login (missing fields) | **Passed** |
| Failed Login (email not registered) | **Passed** |
| Failed Login (wrong password) | **Passed** |
| Failed Login (account pending approval) | **Passed** |
| Authenticated User Redirected From Login Page | **Passed** |
| Logout Success | **Passed** |

#### b. Register & Verifikasi OTP Email
| Scenario | Status |
| :--- | :---: |
| Register Success | **Passed** |
| Failed Register (missing fields) | **Passed** |
| Failed Register (passwords do not match) | **Passed** |
| Failed Register (email already registered) | **Passed** |
| Verify Email OTP Success | **Passed** |
| Failed Verify Email OTP (wrong code) | **Passed** |
| Resend Email OTP Success | **Passed** |

#### c. Lupa & Reset Password via OTP
| Scenario | Status |
| :--- | :---: |
| Request Password Reset Success | **Passed** |
| Verify Password Reset OTP Success | **Passed** |
| Failed Verify Password Reset OTP (wrong code) | **Passed** |
| Reset Password Success | **Passed** |
| Failed Reset Password (passwords do not match) | **Passed** |

#### d. Isolasi Multi-Tenancy Rumah Sakit
| Scenario | Status |
| :--- | :---: |
| Nurse Only Sees Patients from Assigned Hospital | **Passed** |
| Doctor Blocked from Accessing Other Hospital Records | **Passed** |
| Doctor Blocked from Uploading Audio for Other Hospital Patient | **Passed** |
| Doctor Blocked from Streaming Audio of Other Hospital Patient | **Passed** |
| Nurse Blocked from Cancelling Other Hospital Patient | **Passed** |

#### e. Nurse Station - Manajemen Antrean & Input TTV
| Scenario | Status |
| :--- | :---: |
| Register New Patient and Initial Vital Signs Success | **Passed** |
| Search Patient in Queue | **Passed** |
| Record Vital Signs (TTV) for Waiting Patient | **Passed** |
| Visual High Fever Warning Indicator | **Passed** |
| Cancel Patient Registration | **Passed** |

#### f. Poliklinik Dokter - Rekam Medis & AI Medical Scribe
| Scenario | Status |
| :--- | :---: |
| Open Patient Medical Record | **Passed** |
| Review Pre-filled Vital Signs from Nurse | **Passed** |
| Upload Consultation Audio Recording Success | **Passed** |
| Failed Upload Audio (no file attached) | **Passed** |
| Stream Consultation Audio Recording | **Passed** |

---

### Terminal Test Runner Output:
```text
====================================================================
 MEDSCRIBE AI - BEHAVIOR DRIVEN DEVELOPMENT (BDD) TEST RUNNER 
====================================================================

Ditemukan 6 file spesifikasi fitur Gherkin (BDD):
 ▶ 01_autentikasi_dan_otorisasi.feature - Feature: Autentikasi Tenaga Medis (Login & Logout)
   • 8 Skenario BDD
 ▶ 02_registrasi_dan_verifikasi_otp.feature - Feature: Registrasi Akun Tenaga Medis dan Verifikasi OTP Email
   • 7 Skenario BDD
 ▶ 03_lupa_dan_reset_password.feature - Feature: Lupa dan Reset Password via OTP
   • 5 Skenario BDD
 ▶ 04_isolasi_multi_tenancy_rumah_sakit.feature - Feature: Isolasi Data Multi-Tenancy Berbasis Rumah Sakit
   • 5 Skenario BDD
 ▶ 05_nurse_station_pemeriksaan_awal.feature - Feature: Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV
   • 5 Skenario BDD
 ▶ 06_poliklinik_dokter_rekam_medis.feature - Feature: Poliklinik Dokter - Rekam Medis dan AI Medical Scribe
   • 5 Skenario BDD

Menjalankan eksekusi otomasi BDD test suite..

{"tool":"phpunit","result":"passed","tests":95,"passed":95,"assertions":417,"duration_ms":5026}

✓ SEMUA SKENARIO BDD (BEHAVIOR DRIVEN DEVELOPMENT) BERHASIL DIUJI!
```
