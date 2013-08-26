<?php

class DataModel_Manage_Diagnostics extends DataModel_Array{
    
    protected function lazyInitialization() {
        $this->data = array();
        
        // Переменные уровня php
        $this->data[] = array( 'Настройки PHP', $this->isItBad( !$this->toBool(ini_get('register_globals')), 'critical' ), 'Директива register_globals', $this->bool2char( ini_get('register_globals') ), ini_get('register_globals') );
        $this->data[] = array( 'Настройки PHP', $this->isItBad( !$this->toBool(ini_get('allow_url_include')), 'critical' ), 'Директива allow_url_include', $this->bool2char( ini_get('allow_url_include') ), ini_get('allow_url_include') );
        $this->data[] = array( 'Настройки PHP', $this->isItBad( !$this->toBool(ini_get('magic_quotes_gpc')), 'error' ), 'Директива magic_quotes_gpc', $this->bool2char( ini_get('magic_quotes_gpc') ), ini_get('magic_quotes_gpc') );
        $this->data[] = array( 'Настройки PHP', $this->isItBad( ini_get('log_errors'), 'warning' ), 'Запись ошибок в лог', $this->bool2char( ini_get('log_errors') ), ini_get('log_errors') );

        // Размер для загрузки
        $upload = $this->superMind( ini_get( 'upload_max_filesize' ) );
        if ( $upload >= 8000000 ) $decision = 'ok';
        elseif ( $upload >= 2000000 ) $decision = 'warning';
        else $decision = 'error';
        $this->data[] = array( 'Настройки PHP', $decision, 'Максимальный размер загружаемого файла', ini_get( 'upload_max_filesize' ) );

        $post = $this->superMind( ini_get( 'post_max_size' ) );
        if ( $post >= 8000000 && $post >= $upload ) $decision = 'ok';
        elseif ( $post >= 2000000 || $post < $upload ) $decision = 'warning';
        else $post = 'error';
        $this->data[] = array( 'Настройки PHP', $decision, 'Максимальный размер POST', ini_get( 'post_max_size' ) );

        // Переменные уровня ядра
        $this->data[] = array( 'Настройки Standard', $this->isItBad( Configuration::CACHE_ENABLED, 'warning' ), 'Кеширование контента', $this->bool2char( Configuration::CACHE_ENABLED ) );
        $admin_logging = defined( 'Configuration::ADMIN_LOGGING_TABLE' ) && Configuration::ADMIN_LOGGING_TABLE != '';
        $this->data[] = array( 'Настройки Standard', $this->isItBad( $admin_logging, 'error' ), 'Логирование действий админов', ( $admin_logging ? 'таблица '.Configuration::ADMIN_LOGGING_TABLE : 'отключено' ) );
        if ( !defined( 'Configuration::EXCEPTIONS_VIEW_IPS' ) )
                $this->data[] = array( 'Настройки Standard', 'warning', 'Список IP для просмотра Exception', 'отключено' );
        else
                $this->data[] = array( 'Настройки Standard', 'ok', 'Список IP для просмотра Exception', Configuration::EXCEPTIONS_VIEW_IPS );
        if ( !defined( 'Configuration::EXCEPTIONS_VIEW_COOKIE' ) )
                $this->data[] = array( 'Настройки Standard', 'warning', 'Имя Cookie для просмотра Exception', 'отключено' );
        else
                $this->data[] = array( 'Настройки Standard', 'ok', 'Имя Cookie для просмотра Exception', Configuration::EXCEPTIONS_VIEW_COOKIE );

        $exists_404 = file_exists( 'view/404.php' );
        $this->data[] = array( 'Настройки Standard', $this->isItBad( $exists_404, 'error' ), 'Наличие 404.php', ( $exists_404 ? 'присутствует' : 'отсутствует' ) );

        // Настройки SQL
        Core::getDatabase()->getBuilder('plain')
                ->show('GRANTS')
                ->exec();
        $file_privilege = FALSE;
        foreach ( Core::getDatabase()->result as $row ){
            foreach ( $row as $k=>$v ){
                if ( preg_match('%GRANT.*FILE.*ON%', $v) != FALSE ) { $file_privilege = TRUE; break; }
                if ( strpos( $v, 'GRANT ALL PRIVILEGES ON *' ) != FALSE ) { $file_privilege = TRUE; break; }
            }
        }
        $this->data[] = array( 'Настройки Standard', $this->isItBad( !$file_privilege, 'error' ), 'Доступ к файлам через MySQL', ( $file_privilege ? 'присутствует' : 'отсутствует' ) );

        // Файлы .htaccess
        foreach ( array('config/.htaccess','classes/.htaccess','functions/.htaccess','view/.htaccess') as $row ){
            $check = file_exists( Configuration::PATH_LOCAL.$row );
            $this->data[] = array( 'Защита при помощи .htaccess', $this->isItBad( $check, 'error' ), ( $check ? 'Найден' : 'Не найден' ), $row );
        }

        // Пути к папкам
        $bricks = $this->loadBricksInformation();
        foreach ( $bricks as $brick ){
                if ( !is_array( $brick ) || !isset($brick['admin_files']) ) continue;
                foreach ( $brick['admin_files'] as $row ){
                        $path = 'content/'.$row['folder'];

                        $this->data_array = array(
                                'Пути для загрузки контента',
                                'critical',
                                $path,
                                'ошибка'
                        );
                        if ( !file_exists( $path ) || !is_dir( $path ) ) {
                                $this->data_array[1] = 'error';
                                $this->data_array[3] = 'папка не существует';
                        } elseif ( !is_writable( $path ) ){
                                $this->data_array[1] = 'error';
                                $this->data_array[3] = 'нет прав на запись';
                        } else {
                                $this->data_array[1] = 'ok';
                                $this->data_array[3] = 'ок';
                        }

                        $this->data[] = $this->data_array;
                }
        }

        // Кеширование элементов
        foreach ( $bricks as $k=>$brick ){
                if ( is_object( $brick ) )
                        $this->data[] = array( 'Кеширование Bricks', $this->isItBad( FALSE, 'warning' ), 'Настройки кеширования '.get_class($brick).' '.$k, 'неизвестно' );
                elseif ( is_array( $brick ) ){
                        if ( isset($brick['cache']) && is_array($brick['cache']) )
                                $this->data[] = array( 'Кеширование Bricks', 'ok', 'Настройки кеширования Brick '.$k, $brick['cache'][0].' на '.$brick['cache'][1].' сек' );
                        else 
                                $this->data[] = array( 'Кеширование Bricks', 'warning', 'Настройки кеширования Brick '.$k, 'отключено' );
                }
                else 
                        $this->data[] = array( 'Кеширование Bricks', 'critical', 'Настройки кеширования '.$k, 'неправильный brick' );
        }
        
        
    }
    
    protected function isItBad( $value, $badLevel = 'warning' ){
            if ( $this->toBool($value) ) return 'ok';
            return $badLevel;
    }

    protected function toBool( $value ){
            if ( $value === TRUE || $value === FALSE ) return $value;
            if ( strtolower( trim($value) ) == 'on' ) return TRUE;
            if ( strtolower( trim($value) ) == 'off' ) return FALSE;
            if ( $value ) return TRUE;
            return FALSE;
    }

    protected function bool2char( $value ){
            if ( $this->toBool($value) ) return 'включено';
            return 'отключено';
    }

    protected function superMind( $value ){
            $suffix = substr( $value , strlen($value)-2);
            if ( is_numeric( $suffix ) ) return $value;
            $prefix = substr( $value , 0, strlen($value) -1 );
            switch ( $suffix ){
                    case 'k':
                            return $prefix * 1000;
                    case 'g':
                            return $prefix * 1000000000;
                    case 'm':
                    default:
                            return $prefix * 1000000;
            }
    }

    protected function loadBricksInformation(){
            $bricks = array();
            foreach ( Core::$fs->listFilenames( Standard_CoreFS::TYPE_BRICKS ) as $row ){
                    $name = substr( $row, 0, strlen($row)-4);
                    $data = Core::$fs->load( Standard_CoreFS::TYPE_BRICKS , $row);
                    $bricks[$name] = $data;
            }
            return $bricks;
    } 
    
}