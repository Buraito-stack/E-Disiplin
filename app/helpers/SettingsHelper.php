<?php

class SettingsHelper {

    private static $cache = null;

    public static function ensureTable($conn) {
        $conn->query("CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed defaults if empty
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM app_settings");
        if ($result && (int)$result->fetch_assoc()['cnt'] === 0) {
            $defaults = [
                'nama_sekolah'    => 'SMK TI Global Bali',
                'nama_guru_bk'    => 'I Gusti Ayu Rinjani, M.Pd',
                'nama_wakasek'    => 'Bagus Putu Eka Wijaya, S.Kom',
                'nama_kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
            ];
            $stmt = $conn->prepare("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaults as $k => $v) {
                $stmt->bind_param('ss', $k, $v);
                $stmt->execute();
            }
        }
    }

    public static function getAll($conn) {
        if (self::$cache !== null) return self::$cache;

        self::ensureTable($conn);
        $result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
        $settings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        self::$cache = $settings;
        return $settings;
    }

    public static function get($conn, $key, $default = '') {
        $all = self::getAll($conn);
        return $all[$key] ?? $default;
    }

    public static function set($conn, $key, $value) {
        self::ensureTable($conn);
        $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        if ($stmt) {
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
        }
        self::$cache = null;
    }
}
