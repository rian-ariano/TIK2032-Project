<?php
// Mulai session di paling atas untuk menggunakan variabel session
session_start(); 

// Sertakan file koneksi database
include 'koneksi.php'; 

$feedback_message = ''; // Variabel untuk menyimpan pesan error HTML
$javascript_to_run_on_load = ''; // Variabel untuk menyimpan script alert JS

// Cek apakah ada flag untuk menampilkan alert sukses dari session (setelah redirect)
if (isset($_SESSION['show_success_alert']) && $_SESSION['show_success_alert'] === true) {
    $javascript_to_run_on_load = "<script>alert('Pesan Anda berhasil terkirim dan disimpan! Terima kasih.');</script>";
    unset($_SESSION['show_success_alert']); // Hapus flag dari session agar tidak muncul lagi
}

// Pastikan $conn adalah objek mysqli yang valid sebelum digunakan
if ($conn && $conn instanceof mysqli) {
    // Atur autocommit ke ON secara eksplisit
    $conn->autocommit(TRUE);

    // SQL untuk membuat tabel pesan_kontak jika belum ada
    $sql_create_table_pesan = "CREATE TABLE IF NOT EXISTS pesan_kontak (
      id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      nama VARCHAR(100) NOT NULL,
      email VARCHAR(100) NOT NULL,
      pesan TEXT NOT NULL,
      waktu_kirim TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($sql_create_table_pesan)) {
      // Hanya set pesan error jika belum ada pesan dari session (misal alert sukses)
      if (empty($javascript_to_run_on_load)) {
        $feedback_message = "<div class='message error'>Error: Tidak dapat mempersiapkan tabel database. " . $conn->error . "</div>";
      }
    }
} else {
    // Jika koneksi gagal atau variabel koneksi tidak valid
    if (empty($javascript_to_run_on_load)) { // Hanya set jika belum ada pesan alert sukses
        $feedback_message = "<div class='message error'>Error: Koneksi ke database gagal. Silakan coba lagi nanti.</div>";
    }
}


// Cek apakah form telah disubmit (method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan koneksi database berhasil sebelum memproses form
    if (!$conn || !($conn instanceof mysqli) || (property_exists($conn, 'connect_error') && $conn->connect_error)) {
        $feedback_message = "<div class='message error'>Error: Koneksi database terputus. Tidak dapat memproses formulir.</div>";
    } else {
        // Ambil data dari form dan sanitasi dasar
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $pesan = trim($_POST['pesan']);

        // Validasi sederhana (pastikan tidak kosong)
        if (!empty($nama) && !empty($email) && !empty($pesan)) {
            // Validasi format email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Gunakan prepared statement untuk keamanan
                $stmt = $conn->prepare("INSERT INTO pesan_kontak (nama, email, pesan) VALUES (?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("sss", $nama, $email, $pesan);

                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            if ($conn->commit()) {
                                // Set flag di session untuk menampilkan alert setelah redirect
                                $_SESSION['show_success_alert'] = true;
                                // Redirect untuk mencegah resubmission
                                header("Location: contact.php");
                                exit; // Penting untuk menghentikan eksekusi skrip setelah redirect
                            } else {
                                $feedback_message = "<div class='message error'>Pesan terkirim tetapi ada masalah saat konfirmasi penyimpanan. Error: " . $conn->error . "</div>";
                                $conn->rollback();
                            }
                        } else {
                            $feedback_message = "<div class='message error'>Pesan terkirim tetapi tidak ada data yang disimpan. Info error: " . $stmt->error . "</div>";
                        }
                    } else {
                        $feedback_message = "<div class='message error'>Gagal mengirim pesan. Info error: " . $stmt->error . "</div>";
                    }
                    $stmt->close();
                } else {
                    $feedback_message = "<div class='message error'>Gagal mempersiapkan permintaan ke database. Info error: " . $conn->error . "</div>";
                }
            } else {
                $feedback_message = "<div class='message error'>Format email tidak valid. Silakan coba lagi.</div>";
            }
        } else {
            $feedback_message = "<div class='message error'>Semua kolom (Nama, Email, Pesan) wajib diisi.</div>";
        }
    } 
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact - Constantio Ariano Isworo</title>
    <link rel="stylesheet" href="contact.css" />
    <style>
      /* CSS untuk pesan feedback (bisa dipindah ke contact.css jika diinginkan) */
      .message {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
        text-align: center;
        border: 1px solid transparent;
      }
      .success { /* Meskipun alert JS digunakan untuk sukses, style ini tetap ada jika diperlukan */
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
      }
      .error {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
      }
    </style>
  </head>
  <body>
    <header>
      <nav>
        <a href="index.html">Home</a>
        <a href="gallery.html">Gallery</a>
        <a href="blog.html">Blog</a>
        <a href="contact.php" class="active">Contact</a>
      </nav>
    </header>
    <hr />

    <main>
      <div class="container-contact">
        <div class="container-contact-me">
          <div class="container-contact-us">
            <div class="form-container">
              <h2>Kirim Pesan</h2>

              <?php
              // Tampilkan pesan error HTML jika ada (setelah proses POST gagal)
              if (!empty($feedback_message)) {
                  echo $feedback_message;
              }
              ?>

              <form action="contact.php" method="post">
                <div class="form-group">
                  <label for="nama">Nama:</label>
                  <input type="text" id="nama" name="nama" value="<?php echo isset($_POST['nama']) && $_SERVER["REQUEST_METHOD"] == "POST" ? htmlspecialchars($_POST['nama']) : ''; ?>" required />
                </div>
                <div class="form-group">
                  <label for="email">Email:</label>
                  <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) && $_SERVER["REQUEST_METHOD"] == "POST" ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                </div>
                <div class="form-group">
                  <label for="pesan">Pesan:</label>
                  <textarea id="pesan" name="pesan" maxlength="250" required><?php echo isset($_POST['pesan']) && $_SERVER["REQUEST_METHOD"] == "POST" ? htmlspecialchars($_POST['pesan']) : ''; ?></textarea>
                  <small id="counter">250 karakter tersisa</small>
                </div>
                <button type="submit" class="custom-button">Kirim</button>
              </form>
            </div>
          </div>
          <h1 class="h1-contact-me">Contact Me</h1>
          <p>
            Informasi-informasi yang terkait mengenai Email, nomor, media sosal
            lainnya bisa dapat dilihat dan anda juga bisa menghubungi langsung
            dengan mengisi form yang telah disediakan.
          </p>
          <h3>Email :</h3>
          <p>
            <a href="mailto:constantioisworo026@student.unsrat.ac.id" target="_blank"
              >constantioisworo026@student.unsrat.ac.id</a
            >
          </p>
          <h3>Phone :</h3>
          <p>+628XXXXXXXXXX</p>
          <div class="container-social-media">
            <h1>Social Media</h1>
            <a href="https://www.instagram.com/tiosw_/" target="_blank"
              ><svg
                xmlns="http://www.w3.org/2000/svg"
                x="0px"
                y="0px"
                width="50"
                height="50"
                viewBox="0 0 48 48"
              >
                <radialGradient
                  id="yOrnnhliCrdS2gy~4tD8ma_Xy10Jcu1L2Su_gr1_prg_alert"
                  cx="19.38"
                  cy="42.035"
                  r="44.899"
                  gradientUnits="userSpaceOnUse"
                >
                  <stop offset="0" stop-color="#fd5"></stop>
                  <stop offset=".328" stop-color="#ff543f"></stop>
                  <stop offset=".348" stop-color="#fc5245"></stop>
                  <stop offset=".504" stop-color="#e64771"></stop>
                  <stop offset=".643" stop-color="#d53e91"></stop>
                  <stop offset=".761" stop-color="#cc39a4"></stop>
                  <stop offset=".841" stop-color="#c837ab"></stop>
                </radialGradient>
                <path
                  fill="url(#yOrnnhliCrdS2gy~4tD8ma_Xy10Jcu1L2Su_gr1_prg_alert)"
                  d="M34.017,41.99l-20,0.019c-4.4,0.004-8.003-3.592-8.008-7.992l-0.019-20 c-0.004-4.4,3.592-8.003,7.992-8.008l20-0.019c4.4-0.004,8.003,3.592,8.008,7.992l0.019,20 C42.014,38.383,38.417,41.986,34.017,41.99z"
                ></path>
                <radialGradient
                  id="yOrnnhliCrdS2gy~4tD8mb_Xy10Jcu1L2Su_gr2_prg_alert"
                  cx="11.786"
                  cy="5.54"
                  r="29.813"
                  gradientTransform="matrix(1 0 0 .6663 0 1.849)"
                  gradientUnits="userSpaceOnUse"
                >
                  <stop offset="0" stop-color="#4168c9"></stop>
                  <stop
                    offset=".999"
                    stop-color="#4168c9"
                    stop-opacity="0"
                  ></stop>
                </radialGradient>
                <path
                  fill="url(#yOrnnhliCrdS2gy~4tD8mb_Xy10Jcu1L2Su_gr2_prg_alert)"
                  d="M34.017,41.99l-20,0.019c-4.4,0.004-8.003-3.592-8.008-7.992l-0.019-20 c-0.004-4.4,3.592-8.003,7.992-8.008l20-0.019c4.4-0.004,8.003,3.592,8.008,7.992l0.019,20 C42.014,38.383,38.417,41.986,34.017,41.99z"
                ></path>
                <path
                  fill="#fff"
                  d="M24,31c-3.859,0-7-3.14-7-7s3.141-7,7-7s7,3.14,7,7S27.859,31,24,31z M24,19c-2.757,0-5,2.243-5,5 s2.243,5,5,5s5-2.243,5-5S26.757,19,24,19z"
                ></path>
                <circle cx="31.5" cy="16.5" r="1.5" fill="#fff"></circle>
                <path
                  fill="#fff"
                  d="M30,37H18c-3.859,0-7-3.14-7-7V18c0-3.86,3.141-7,7-7h12c3.859,0,7,3.14,7,7v12 C37,33.86,33.859,37,30,37z M18,13c-2.757,0-5,2.243-5,5v12c0,2.757,2.243,5,5,5h12c2.757,0,5-2.243,5-5V18c0-2.757-2.243-5-5-5H18z"
                ></path></svg
            ></a>
            <a
              href="https://www.linkedin.com/in/ariano-constantio-30266a1b9/"
              target="_blank"
              ><svg
                xmlns="http://www.w3.org/2000/svg"
                x="0px"
                y="0px"
                width="50"
                height="50"
                viewBox="0 0 48 48"
              >
                <path
                  fill="#0078d4"
                  d="M42,37c0,2.762-2.238,5-5,5H11c-2.761,0-5-2.238-5-5V11c0-2.762,2.239-5,5-5h26c2.762,0,5,2.238,5,5 V37z"
                ></path>
                <path
                  d="M30,37V26.901c0-1.689-0.819-2.698-2.192-2.698c-0.815,0-1.414,0.459-1.779,1.364 c-0.017,0.064-0.041,0.325-0.031,1.114L26,37h-7V18h7v1.061C27.022,18.356,28.275,18,29.738,18c4.547,0,7.261,3.093,7.261,8.274 L37,37H30z M11,37V18h3.457C12.454,18,11,16.528,11,14.499C11,12.472,12.478,11,14.514,11c2.012,0,3.445,1.431,3.486,3.479  C18,16.523,16.521,18,14.485,18H18v19H11z"
                  opacity=".05"
                ></path>
                <path
                  d="M30.5,36.5v-9.599c0-1.973-1.031-3.198-2.692-3.198c-1.295,0-1.935,0.912-2.243,1.677 c-0.082,0.199-0.071,0.989-0.067,1.326L25.5,36.5h-6v-18h6v1.638c0.795-0.823,2.075-1.638,4.238-1.638  c4.233,0,6.761,2.906,6.761,7.774L36.5,36.5H30.5z M11.5,36.5v-18h6v18H11.5z M14.457,17.5c-1.713,0-2.957-1.262-2.957-3.001  c0-1.738,1.268-2.999,3.014-2.999c1.724,0,2.951,1.229,2.986,2.989c0,1.749-1.268,3.011-3.015,3.011H14.457z"
                  opacity=".07"
                ></path>
                <path
                  fill="#fff"
                  d="M12,19h5v17h-5V19z M14.485,17h-0.028C12.965,17,12,15.888,12,14.499C12,13.08,12.995,12,14.514,12  c1.521,0,2.458,1.08,2.486,2.499C17,15.887,16.035,17,14.485,17z M36,36h-5v-9.099c0-2.198-1.225-3.698-3.192-3.698 c-1.501,0-2.313,1.012-2.707,1.99C24.957,25.543,25,26.511,25,27v9h-5V19h5v2.616C25.721,20.5,26.85,19,29.738,19 c3.578,0,6.261,2.25,6.261,7.274L36,36L36,36z"
                ></path></svg
            ></a>
            <a href="https://github.com/rian-ariano" target="_blank"
              ><svg
                xmlns="http://www.w3.org/2000/svg"
                x="0px"
                y="0px"
                width="50"
                height="50"
                viewBox="0 0 32 32"
              >
                <path
                  fill-rule="evenodd"
                  d="M 16 4 C 9.371094 4 4 9.371094 4 16 C 4 21.300781 7.4375 25.800781 12.207031 27.386719 C 12.808594 27.496094 13.027344 27.128906 13.027344 26.808594 C 13.027344 26.523438 13.015625 25.769531 13.011719 24.769531 C 9.671875 25.492188 8.96875 23.160156 8.96875 23.160156 C 8.421875 21.773438 7.636719 21.402344 7.636719 21.402344 C 6.546875 20.660156 7.71875 20.675781 7.71875 20.675781 C 8.921875 20.761719 9.554688 21.910156 9.554688 21.910156 C 10.625 23.746094 12.363281 23.214844 13.046875 22.910156 C 13.15625 22.132813 13.46875 21.605469 13.808594 21.304688 C 11.144531 21.003906 8.34375 19.972656 8.34375 15.375 C 8.34375 14.0625 8.8125 12.992188 9.578125 12.152344 C 9.457031 11.851563 9.042969 10.628906 9.695313 8.976563 C 9.695313 8.976563 10.703125 8.65625 12.996094 10.207031 C 13.953125 9.941406 14.980469 9.808594 16 9.804688 C 17.019531 9.808594 18.046875 9.941406 19.003906 10.207031 C 21.296875 8.65625 22.300781 8.976563 22.300781 8.976563 C 22.957031 10.628906 22.546875 11.851563 22.421875 12.152344 C 23.191406 12.992188 23.652344 14.0625 23.652344 15.375 C 23.652344 19.984375 20.847656 20.996094 18.175781 21.296875 C 18.605469 21.664063 18.988281 22.398438 18.988281 23.515625 C 18.988281 25.121094 18.976563 26.414063 18.976563 26.808594 C 18.976563 27.128906 19.191406 27.503906 19.800781 27.386719 C 24.566406 25.796875 28 21.300781 28 16 C 28 9.371094 22.628906 4 16 4 Z"
                ></path></svg
            ></a>
          </div>
        </div>
      </div>
    </main>
    <hr />

    <footer>
      <div>
        <p>&copy; 2025 - Constantio Ariano Isworo</p>
      </div>
    </footer>
    <?php
    // Cetak script alert jika ada
    if (!empty($javascript_to_run_on_load)) {
        echo $javascript_to_run_on_load;
    }

    // Tutup koneksi database di akhir file setelah semua output HTML selesai
    if (isset($conn) && $conn instanceof mysqli) { 
        $conn->close();
    }
    ?>
  </body>
</html>