<?php

class Controller_Standard_Manage implements StandardEventReciever {
	protected $bricks;

	public function bindEvent( $eventID, StandardEventReciever $callbackFunction ){
		// Do nothing
	}

	public final function castEvent( $eventID ){
		if ( !in_array($eventID, array(
		StandardEventReciever::START_ADMIN_AJAX,
		StandardEventReciever::START_HTML,
		StandardEventReciever::START_ADMIN
		)) ) return;

		Core::$reg->meta->title->push('Панель управления');

		// Устанавливаем папку для рендеринга
		$folder = Core::$out->getFolder();
		if ( !is_array( $folder ) ) $folder = array($folder);
		Core::$out->setFolder( array_merge( array( Configuration::PATH_FRAMEWORK.'view/manage/' ), $folder ) );

		// Проверка на proxy
		if ( Core::$in->_navigation[1] == 'proxy' ) $this->makeProxy();

		// Проверяем, есть ли активный пользователь
		if ( !isset( Core::$auth ) || !is_object( Core::$auth ) || ! Core::$auth instanceof Auth_Abstract ) throw new StandardException("No suitable authenticator");

		// Проверка сессии
		$this->requestLogin( $eventID );

		// Подготавливаем админ данные
		$this->loadBricksInformation();
		$this->createMenu();

		// Ветвление
		switch ( Core::$in->_navigation[1] ){
			case 'logout':
				Core::$auth->dropSession();
				Core::$out->redirect('/'.Configuration::MANAGE_URL.'/');
			case 'data':
				if ( !isset( Core::$in->_navigation[2], $this->bricks, $this->bricks[Core::$in->_navigation[2]] ) ) throw new StandardException("Brick not found");
				if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_READ ) ) throw new Exception_UserNotPrivileged();

				if ( Core::$in->_navigation[3] == 'export' ){
					if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_EXPORT ) ) throw new Exception_UserNotPrivileged();
					$obj = new Manage_ORM_Controller_Export(Core::$in->_navigation[2], $this->bricks[Core::$in->_navigation[2]]);
					$obj->castEvent( $eventID );
					exit;
				}

				if ( isset($this->bricks[Core::$in->_navigation[2]]['behaviour']) && $this->bricks[Core::$in->_navigation[2]]['behaviour'][0] == 'hash' ){
					$obj = new Manage_ORM_Controller_Hashtable(Core::$in->_navigation[2], $this->bricks[Core::$in->_navigation[2]]);
					$obj->castEvent( $eventID );
					break;
				}
				if ( Core::$in->_navigation[3] == 'list' ){
                                        $obj = new Manage_ORM_Controller_List(Core::$in->_navigation[2], $this->bricks[Core::$in->_navigation[2]]);
					$obj->castEvent( $eventID );
				} elseif ( Core::$in->_navigation[3] == 'fill' ){
                                        $obj = new Manage_ORM_Controller_Fill(Core::$in->_navigation[2], $this->bricks[Core::$in->_navigation[2]]);
					$obj->castEvent( $eventID );
				} elseif ( Core::$in->_navigation[3] == 'add' || Core::$in->_navigation[3] == 'edit' || Core::$in->_navigation[3] == 'history' ){
                                        if ( isset($this->bricks[Core::$in->_navigation[2]]['admin_controller_edit']) ){
                                            $class = $this->bricks[Core::$in->_navigation[2]]['admin_controller_edit'];
                                            $obj = new $class();
                                        }else
					$obj = new Manage_ORM_Controller_AddEdit(Core::$in->_navigation[2], $this->bricks[Core::$in->_navigation[2]]);
					$obj->castEvent( $eventID );
				}
				break;
			case 'system':
				if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_SYSTEM ) ) throw new Exception_UserNotPrivileged();
				if ( Core::$in->_navigation[2] == 'diagnostic' ){
					$obj = new Manage_DiagnosticPage();
					$obj->castEvent($eventID);
				}elseif ( Core::$in->_navigation[2] == 'structure' ){
					$obj = new Manage_StructurePage();
					$obj->castEvent($eventID);
				}elseif ( Core::$in->_navigation[2] == 'cache' ){
					$obj = new Manage_Cache();
					$obj->castEvent($eventID);
				}elseif ( Core::$in->_navigation[2] == 'processes' ){
					$obj = new Manage_ProcessesPage();
					$obj->castEvent($eventID);
				}elseif ( Core::$in->_navigation[2] == 'compile' ){
					if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_ENGINEER ) ) throw new Exception_UserNotPrivileged();
					$obj = new Manage_Compiler();
					$obj->castEvent($eventID);
				}
				break;
                        case 'reports':
                                if ( !method_exists('Configuration', 'getReports') ) throw new StandardException("Reports not configured");
                                // Подключаем отчёты
                                $reports = Configuration::getReports();
                                $obj = new Manage_ReportController();
				if ( isset($reports['table']) && !empty($reports['table']) ) $obj->bindTable($reports['table']);
				if ( !is_numeric(Core::$in->_navigation[2]) && Core::$in->_navigation[2] != 'temporary_reports' && Core::$in->_navigation[2] != 'saved_reports' ){
				    if ( !isset($reports['reports'][Core::$in->_navigation[2]]) ) throw new StandardException("Report not found");
                                    $a = $reports['reports'][Core::$in->_navigation[2]]['report'];
				    if ( isset( $reports['reports'][Core::$in->_navigation[2]]['rights'] ) && !Core::$auth->isAllowed($reports['reports'][Core::$in->_navigation[2]]['rights']) ) continue;
				    $obj->bindReport( new $a() );
				}
                                $obj->castEvent($eventID);
                                break;
			case 'extra':
				// Подключаем экстра
				foreach ( Configuration::getAdminExtra() as $id=>$row ){
					if ( $id != Core::$in->_navigation[2] ) continue;
					if ( isset($row['rights']) && !Core::$auth->isAllowed($row['rights']) ) throw new Exception_UserNotPrivileged("You are not allowed for this extra");
					$a = $row['event_handler'];
					$a = new $a();
					$a->bindSaveBundle( Core::getSaveBundle() );
					$a->castEvent( $eventID );
				}
				break;
			case 'user':
				switch ( Core::$in->_navigation[2] ){
					case 'log':
						$obj = new Manage_Log();
						$obj->castEvent($eventID);
						break;
					case 'users':
						$obj = new Manage_Users();
						$obj->castEvent($eventID);
						break;
				}
				break;
		}

                $obj = new Manage_Dashboard();
                $obj->castEvent($eventID);
		Core::$out->show( 'dashboard' );
	}

	protected function loadBricksInformation(){
		$this->bricks = array();
                foreach ( Core::$fs->listFilenames( Standard_CoreFS::TYPE_BRICKS ) as $row ){
                    $name = substr( $row, 0, strlen($row)-4);
                    $data = Core::$fs->load( Standard_CoreFS::TYPE_BRICKS , $row);
                    if ( !is_array( $data ) ) continue;
                    if ( isset($data['use_edit']) && $data['use_edit'] == false ) continue;
                    $this->bricks[$name] = $data;
                }
	}

	protected function requestLogin( $eventID ){
		Core::$reg->data['LoginPage'] = new DataModel_Hashtable();
		if ( Core::$auth->isHere() ){
			// Пользователь уже залогинен
			if ( !Core::$auth->isPrivileged() ) {
				if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('error', 'Not privileged');
				Core::$auth->dropSession();
				header('HTTP/1.0 403 Forbidden', 403);
				exit;
			}
			return; // Всё окей
		}
		if ( isset( Core::$in->_post['login'], Core::$in->_post['password'] ) || isset( Core::$in->_post['new_password'] ) ){
			// Проверяем логин и пароль
			try{
				if ( isset( Core::$in->_post['new_password'] ) ){
					if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('error', 'Password change requested. Please use web interface');
					// смена пароля
					$this->resetPassword();
				}else{
					$uid = Core::$auth->checkCredentials( array('login'=>Core::$in->_post['login'], 'password'=>Core::$in->_post['password']) );
					Core::$auth->startSession($uid);
					if ( !Core::$auth->isPrivileged() ) throw new Exception_UserNotPrivileged();
				}
				if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('ok', 'Login successful');
				Core::$out->redirect( $_SERVER['REQUEST_URI'] );
			} catch ( Exception_UserNotFound $e ){
				if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('error', 'User not found');
				Core::$reg->data['LoginPage']->replace('error', 'Пользователь не найден' );
			} catch ( Exception_UserBanned $e ){
				if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('error', 'User banned');
				Core::$reg->data['LoginPage']->replace('error', 'Ваша учётная запись заблокирована' );
			} catch ( Exception_UserNotPrivileged $e ){
				if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('error', 'Not privileged');
				Core::$reg->data['LoginPage']->replace('error', 'Ваша учётная запись не обладает достаточными правами для администрирования' );
			} catch ( Exception_UserPasswordReset $e ){
				if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('error', 'Password change requested. Please use web interface');
				Core::$reg->data['LoginPage']->replace('error', 'Для вашей учётной записи требуется смена пароля' );
				Core::$reg->data['LoginPage']->replace('reset', 'yes');
			}
		}

		if ( $eventID === StandardEventReciever::START_ADMIN_AJAX ) $this->AJAXReply('welcome', Core::$auth->getAbout());

		Core::$reg->data['LoginPage']->replace('about', Core::$auth->getAbout() );
		Core::$out->show( 'login' );
	}

	private function AJAXReply( $status, $message ){
		if ( $status === 'error' )
			die( JSON::encode( array('status'=>'error' , 'error' => $message ) ) );

		die( JSON::encode( array('status'=>$status , 'data' => $message )));
	}

	protected function resetPassword(){
		if ( !method_exists( Core::$auth, 'resetPassword') ) return;
		Core::$auth->resetPassword( Core::$in->_post['login'], Core::$in->_post['old_password'], Core::$in->_post['new_password'] );
	}

	protected function createMenu(){

                $menu = array();
            
		if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_READ ) ){
                    $dataGroups = array();
                    if ( isset( $this->bricks ) && count($this->bricks) ) foreach ( $this->bricks as $k=>$v ){
                        $key = ( isset( $v['section'] ) ? $v['section'] : 'Данные' );
                        if ( !isset($dataGroups[$key]) ) $dataGroups[$key] = array();
                        $row = array();
                        if ( isset($v['name']) ) $row['name'] = $v['name'];
                        else $row['name'] = str_replace('_', ' ', $k);
                        $row['relative_url'] = array('data',$k,'list');
                        $row['icon'] = 'fam/table.png';

                        if ( isset($v['behaviour']) && $v['behaviour'][0] == 'hash' ) $row['icon'] = 'fam/table_gear.png';

                       $dataGroups[$key][] = $row;
                    }
                    
                    if (count($dataGroups) > 0 ) foreach ( $dataGroups as $k=>$v ){
                        $menu['data_'.$k] = array( 'name'=>$k, 'icon'=>'fam/folder.png', '_'=>$v );
                    }
		}

		// Дополнительные пункты
		if ( method_exists('Configuration', 'getAdminExtra') ){
			foreach ( Configuration::getAdminExtra() as $id=>$row ){
				if ( isset( $row['rights'] ) && !Core::$auth->isAllowed($row['rights']) ) continue;

				if ( !isset($menu[$row['section']]) )
					$menu[$row['section']] = array('name'=>$row['section'],'_'=>array(),'icon'=>$row['section_icon']);

				$menu[$row['section']]['_'][] = array(
					'name'=>$row['name'],
					'icon'=>$row['icon'],
					'relative_url'=>array('extra',$id)
				);
			}
		}

		$menu['user'] = array('name'=>'Реестр','icon'=>'fam/book.png','_'=>array());

		// Отчёты
                if ( method_exists('Configuration', 'getReports') ){
			$reports = Configuration::getReports();
			// Временные отчёты
			if ( isset( $reports['table'] ) ){
			    Core::getDatabase()->getBuilder('plain')
				    ->select( $reports['table'], 'count(*) as amount FROM ?? WHERE `saved`="1" AND `user_created`=?', array( Core::$auth->getUID() ) )
				    ->exec();
			    if ( Core::getDatabase()->result->getValue('amount') > 0 ){
				$menu['user']['_'][] = array(
					'name'=>'Сохранённые отчёты ('.Core::getDatabase()->result->getValue('amount').')',
					'icon'=>'fam/book_open.png',
					'relative_url'=>array('reports','saved_reports')
				);
			    }
			    Core::getDatabase()->getBuilder('plain')
				    ->select( $reports['table'], 'count(*) as amount FROM ?? WHERE `saved`="0" AND `user_created`=?', array( Core::$auth->getUID() ) )
				    ->exec();
			    if ( Core::getDatabase()->result->getValue('amount') > 0 ){
				$menu['user']['_'][] = array(
					'name'=>'Недавние отчёты ('.Core::getDatabase()->result->getValue('amount').')',
					'icon'=>'fam/book_open.png',
					'relative_url'=>array('reports','temporary_reports')
				);
			    }
			}

			// Генерация отчётов
                        foreach ( $reports['reports'] as $id=>$row ){
				if ( isset( $row['rights'] ) && !Core::$auth->isAllowed($row['rights']) ) continue;
				$menu['user']['_'][] = array(
					'name'=>$row['name'],
					'icon'=>'fam/report.png',
					'relative_url'=>array('reports',$id)
				);
                        }
                }
		if ( defined('Configuration::ADMIN_LOGGING_TABLE') && Configuration::ADMIN_LOGGING_TABLE != '' && Core::$auth->isAllowed( Auth_Abstract::ACCESS_MODERATOR ) )
			$menu['user']['_'][] = array( 'relative_url'=>array('user','log'), 'name'=>'История правок', 'icon'=>'fam/database_edit.png' );


		$menu['system'] = array('name'=>'Система','_'=>array(),'icon'=>'fam/cog.png','disabled'=>false);
		if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_SYSTEM ) ){
			$menu['system']['_'] = array(
				array('relative_url'=>array('system','cache'), 'icon'=>'fam/lightbulb.png', 'name'=>'Кеширование','disabled'=>!Configuration::CACHE_ENABLED),
				array('relative_url'=>array('system','diagnostic'), 'icon'=>'fam/information.png', 'name'=>'Диагностика'),
				array('relative_url'=>array('system','structure'), 'icon'=>'fam/application_side_tree.png', 'name'=>'Структура'),
				array('relative_url'=>array('system','processes'), 'icon'=>'fam/lightning.png', 'name'=>'Процессы')
			);

                        try{
                            if ( Core::$auth->isAllowed( Auth_Abstract::ACCESS_ENGINEER ) && defined( 'Configuration::COMPILATION_TEMPLATES' ) )
                                $menu['system']['_'][] = array('relative_url'=>array('system','compile'), 'icon'=>'fam/script_gear.png', 'name'=>'Компиляция');
                        } catch ( Exception $e ){}
		}


		if ( Core::$auth instanceof Auth_Common && Core::$auth->isAllowed( Auth_Abstract::ACCESS_USERS_READ ) )
			$menu['system']['_'][] = array( 'relative_url'=>array('user','users'), 'name'=>'Пользователи', 'icon'=>'fam/report_user.png' );



		$menu['logout'] = array('name'=>'Выход','icon'=>'fam/door.png','relative_url'=>'logout');

		// Устанавливаем кнопки
		Core::$reg->data->buttons = new DataModel_Array();
		Core::$reg->data->buttons->push(
			array(
				'name'=>'Панель управления',
				'_'=>array(
					array('type'=>'text','name'=>'Ваш ID', 'value'=>Core::$auth->getUID()),
					array('type'=>'text','name'=>'Время сервера', 'value'=>date('d/m/y H:i')),
					//array('type'=>'relative_url','name'=>'Выйти','icon'=>'fam/door.png', 'url'=>'logout')
                                        array('type'=>'onclick','onclick'=>'ManageJS.toggleUI()','name'=>'Скрыть','icon'=>'fam/star.png')
				)
			)
		);

		// Сортируем
		if ( isset($menu['data'],$menu['data']['_']) && count($menu['data']['_']) ) $menu['data']['_'] = Helper_ArraySorter::Sort( $menu['data']['_'] , Helper_ArraySorter::NESTED_VALUES_CYRRILLIC, 'name');

		Core::$reg->data['ManageMenu'] = $menu;
	}

	protected function makeProxy(){
		// Определяем URL
		$address = implode('/', array_slice( Core::$in->_navigation->getArray() , 2));

		// Избавляемся от хитрожопых
		if (strpos($address, '..') !== FALSE ) throw new StandardException("Smart ass detected");
		$address = Configuration::PATH_FRAMEWORK.'html/manage/'.$address;

		if ( !file_exists( $address ) || !is_readable( $address ) ) {
			header("HTTP/1.0 404 Not Found", 404);
			exit;
		}

		$last = substr($address, -3);
		if ( $last === 'css' ) header('Content-Type: text/css');
		elseif ( $last === '.js' ) header('Content-Type: application/javascript');

		echo file_get_contents($address);
		exit;
	}
}