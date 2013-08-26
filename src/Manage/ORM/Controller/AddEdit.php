<?php

class Manage_ORM_Controller_AddEdit extends Manage_ORM_Controller_List{
	
	public function castEvent( $eventID ){
		if ( $eventID == StandardEventReciever::START_HTML ) $this->buildEntry();
		if ( $eventID == StandardEventReciever::START_ADMIN ) {
                    if ( Core::$in->_navigation[4] > 0 && Core::$in->_navigation[3] == 'history' ) return $this->buildHistory ();
                    $this->buildEntry();
                }
                if ( $eventID == StandardEventReciever::START_ADMIN_AJAX ){
                    $this->prepareEverything();
                    if ( Core::$in->_post['DeleteIllustration'] == 'yes' ) die( $this->DeleteIllustration( Core::$in->_navigation[4], Core::$in->_post['illustration']) );
                    else {
                        $this->buildJSEntry();
                    }
                }
	}	
	
        protected function buildHistory(){
                if ( !Core::$auth->isAllowed( Auth_Abstract::ACCESS_MODERATOR ) ) return;
                $this->prepareEverything();
                $id = (int) Core::$in->_navigation[4];
                $bundle = Core::getSaveBundle();
                Core::$reg->data['List'] = $bundle->fetchHistory( $this->data['table'] , $id);
                $view = 'log_list';
                // Кнопки
                Core::$reg->data->buttons->push( array(
			'name'=>'Сервис',
			'__'=>array(
                                array( 'type'=>'relative_url','name'=>'Карточка','icon'=>'fatcow/page_edit.png','url'=>array('data',$this->name,'edit',$id)),
				array( 'type'=>'relative_url','name'=>'История','icon'=>'fatcow/book_edit.png','url'=>array('data',$this->name,'history',$id)),
			)
		) );
		
		Core::$out->show( 'log_list' );                
        }
        
        protected function buildJSEntry(){
                $id = (int) Core::$in->_navigation[4];
                $f = new DataModel_Fetcher( $this->data['table'] );
                $f->setShowChecking(false);
                $f->addFilter('id',$id);
                $f->setLimit(1);
                $defaults = $f[0];

                die (JSON::encode(array('entry'=>$defaults)));

        }
        
	protected function buildEntry(){
		$this->prepareEverything();
		$id = (int) Core::$in->_navigation[4];
		$defaults = array();
		if ( $id === 0 && Core::$in->_navigation[3] == 'add' ){
			Core::$reg->meta->title->push( 'Добавление' );
			if ( count( Core::$in->_post ) ) $this->mustSave();
			// Режим добавления
			Core::$reg->data['ORMMode'] = 'add';
                } elseif ( $id > 0 && Core::$in->_navigation[3] == 'edit' && Core::$in->_navigation[5] == 'delete' ){
                        // Поддержка множественного удаления
                        foreach( explode(',',Core::$in->_navigation[4]) as $id ){
                            $id = (int) $id;
                            if ( $id < 1 ) continue;
                            $f = new DataModel_Fetcher( $this->data['table'] );
                            $f->setShowChecking(false);
                            $f->addFilter('id',$id);
                            $f->setLimit(1);
                            $defaults = $f[0];
                            if ( $f->isEmpty() ) throw new StandardException("Unable to get data on id=$id");
                            foreach ( $this->structure->getFields() as $row )
					$row->prepareDelete( $defaults );
                            $bundle = Core::getSaveBundle();
                            $bundle->deleteById( $id , $this->data['table']);
                            // Удаляем иллюстрации
                            if ( isset($this->data['illustrations']) && is_array( $this->data['illustrations'] ) ){
                                $db = Core::getDatabase();
                                $db->getBuilder()
                                ->select($this->data['illustrations']['table'], '`id` FROM ?? WHERE `type` = ? AND `id_entity` = ?',array( $this->data['illustrations']['type'], $id ))
                                ->exec();

                                if ( count($db->result) ) foreach ( $db->result->getData() as $row ) $this->DeleteIllustration ($id, $row['id']);
                            }
                        }
                        Core::$out->redirect( new Renderable_URL( array('data',$this->name,'list') ) );
		} elseif ( $id > 0 && Core::$in->_navigation[3] == 'edit' ){
			Core::$reg->meta->title->push( '№'.$id );
                        if ( Core::$in->_post['UploadMode'] == 'Illustration' ) $this->UploadIllustration( $id );
			if ( count( Core::$in->_post ) ) $this->mustSave( $id );
			// Режим редактирования
			Core::$reg->data['ORMMode'] = 'edit';
			$f = new DataModel_Fetcher( $this->data['table'] );
			$f->setShowChecking(false);
			$f->addFilter('id',$id);
			$f->setLimit(1);
			$defaults = $f[0];
			if ( $f->isEmpty() ) throw new StandardException("Unable to get data on id=$id");
		}else throw new StandardException("Segmentation fault");
		// Если вдруг есть - перезаписываем из _POST
		if ( count(Core::$in->_post) ) $defaults = Core::$in->_post;
		
		// Глобальный readonly
		$cantTouchThis = ( isset($this->data['admin_readonly']) && $this->data['admin_readonly'] );
		if ( !$cantTouchThis && !Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_WRITE ) ) $cantTouchThis = TRUE;
		
		// Подключены иллюстрации
		if ( $id > 0 && isset($this->data['illustrations']) && is_array( $this->data['illustrations'] ) ){
			$illustrations = array();
			// Читаем из БД
			$db = Core::getDatabase();
			$db->getBuilder()
			->select($this->data['illustrations']['table'], '`id`,`image_name`,`image_ext` FROM ?? WHERE `type` = ? AND `id_entity` = ?',array( $this->data['illustrations']['type'], $id ))
			->exec();
			
			if ( count($db->result) ) foreach ( $db->result as $row )
				$illustrations[ $row['id'] ] = $row;
			Core::$reg->data['ORMIllustrations'] = $illustrations;
		}
		
		// Кнопки
		Core::$reg->data->buttons->push( array(
			'name'=>'Данные',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'Добавить','icon'=>'fatcow/document_import.png','url'=>array('data',$this->name,'add'), 'disabled'=>$cantTouchThis),
				array( 'type'=>'relative_url','name'=>'К списку','icon'=>'fatcow/directory_listing.png','url'=>array('data',$this->name,'list')),
				array( 'type'=>'javascript','name'=>'Сохранить','icon'=>'fatcow/save_as.png','id'=>'SaveButton', 'disabled'=>$cantTouchThis),
				array( 'type'=>'relative_url','name'=>'Клонировать','icon'=>'fatcow/document_move.png','url'=>array('data',$this->name,'add'), 'disabled'=>true),
				array( 'type'=>'javascript','name'=>'Удалить','icon'=>'fatcow/bin.png','id'=>'DeleteButton', 'disabled'=>(Core::$reg->data['ORMMode'] == 'add'), 'disabled'=>$cantTouchThis),
			)
		) );
                Core::$reg->data->buttons->push( array(
			'name'=>'Сервис',
			'__'=>array(
				array( 'type'=>'relative_url','name'=>'История','icon'=>'fatcow/book_edit.png','url'=>array('data',$this->name,'history',$id), 'disabled'=>!Core::$auth->isAllowed( Auth_Abstract::ACCESS_MODERATOR )),
			)
		) );
		
		Core::$reg->data['ORMDefaults'] = $defaults;
		Core::$out->show( 'orm/edit' );
	}
	
	protected function mustSave( $id = NULL ){
		$old = NULL;
		
		// Проверяем права
		$cantTouchThis = ( isset($this->data['admin_readonly']) && $this->data['admin_readonly'] );
		if ( !$cantTouchThis && !Core::$auth->isAllowed( Auth_Abstract::ACCESS_DATA_MODELS_WRITE ) ) $cantTouchThis = TRUE;
		if ( $cantTouchThis === TRUE ) throw new Exception_UserNotPrivileged();
		
		// Читаем старые данные
		if ( isset($id) ){
			$f = new DataModel_Fetcher( $this->data['table'] );
			$f->setShowChecking(false);
			$f->addFilter('id',$id);
			$f->setLimit(1);
			if ( $f->isEmpty() ) throw new StandardException("Unable to get data on id=$id");
			$old = $f[0];
		}
		
		$bundle = Core::getSaveBundle();
		$saveArray = array();
		foreach ( $this->structure->getFields() as $row ){
			$saveArray = array_merge( $saveArray, $row->prepareSaveData( $old, Core::$in->_post ) );
		}
		
		
		if ( count( $saveArray ) ){
			// Есть что сохранять
			if ( Core::$in->_navigation[3] === 'add' ){
				$id = $bundle->insertInto( $this->data['table'] , $saveArray );
			} else {
				$id = (int) Core::$in->_navigation[4];
				$bundle->replaceIn( $id, $this->data['table'], $saveArray );
			}
			
			// Если есть кеш - ревалидируем
			if ( Configuration::CACHE_ENABLED && isset($this->data['cache']) && is_array($this->data['cache']) ){
				$cache_entries = array();
				if ( strpos($this->data['cache'][0], '$') === FALSE ){
					$cache_entries[] = $this->data['cache'][0];
				} elseif ( Configuration::I18N ) {
					// Если единственные замены - языковые
					$test = str_replace('$L', '', $this->data['cache'][0]);
					if ( strpos( $test , '$') === FALSE ){
						foreach ( Core::getI18NLanguages() as $row )
							$cache_entries[] = str_replace('$L', $row, $this->data['cache'][0]);
					}
				}
				foreach ( $cache_entries as $row ) Cache::Erase( $row );
			}
			
			Core::$out->redirect( new Renderable_URL( array('data',$this->name,'edit',$id), array('saved'=>'yes') ) );
		}
		
		die('saving');
	} 

        protected function UploadIllustration( $id ){
            // Сохраняем иллюстрацию
            if ( isset($id) && isset($this->data['illustrations']) && isset( $_FILES ) && isset($_FILES['MediantIllustration']) && isset($_FILES['MediantIllustration']['tmp_name']) && $_FILES['MediantIllustration']['tmp_name'] != '' ){
                    // Сохраняем иллюстрацию
                    $up = File_Upload::createOnIncomingFiles($_FILES['MediantIllustration']);
                    $up->uploadTo( 'content/'.$this->data['illustrations']['folder'], time().'il'.(Core::$auth->getUID()*2) );
                    $illustrationsBundle = Core::getSaveBundle();
                    $illustrationsBundle->insertInto( $this->data['illustrations']['table'] , array(
                            'type'=>$this->data['illustrations']['type'],
                            'id_entity'=>$id,
                            'image_name'=>$up->getSavedName(),
                            'image_ext'=>$up->getSavedExt()
                    ));
                // Вывод
                die("<script>window.parent.ManageJS.showUploadedIllustration('/content/".$this->data['illustrations']['folder']."/".$up->getSavedName().".".$up->getSavedExt()."');</script>");
            }
            die();
        }
        
        protected function DeleteIllustration( $id, $id_illustration ){
            $id = (int) $id;
            $id_illustration = (int) $id_illustration;
            // Ищем иллюстрацию 
            Core::getDatabase()->getBuilder()
                    ->select( $this->data['illustrations']['table'] , '* FROM ?? WHERE `id` = ? AND `type` = ? AND `id_entity` = ? LIMIT 1', array(
                        $id_illustration,
                        $this->data['illustrations']['type'],
                        $id
                    ))
                    ->exec();
            if ( count(Core::getDatabase()->result) == 0 ) return FALSE;
            $row = Core::getDatabase()->result->getRow();
            // Удаляем запись
            Core::getDatabase()->getBuilder()
                    ->delete($this->data['illustrations']['table'], $id_illustration)
                    ->exec();
            
            // Удаляем файл
            $filename = 'content/'.$this->data['illustrations']['folder'].'/'.$row['image_name'].'.'.$row['image_ext'];
            if ( !file_exists($filename) || !is_writable($filename) ) return false;
            unlink($filename);
            return true;
        }
        
}