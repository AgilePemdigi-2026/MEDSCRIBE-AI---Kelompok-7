Feature: Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV
  In order to prepare and record objective patient vital signs for outpatient consultation
  As a clinic nurse
  I want to manage the queue, register new patients, and record vital signs (TTV)

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
