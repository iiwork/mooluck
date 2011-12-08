<?php
/**
 * 이벤트 등록, 해제, 발생
 * 
 * @class Event
 * @author mini <i@iiwork.com>
 */
Class iiEvent {
	private $_aListeners = array();
	
	/**
	 * 이벤트 리스너 등록
	 * 
	 * @param String $name 이벤트 이름
	 * @param Function $listener 이벤트 리스너
	 */
	public function addEvent($name, $listener) {
		if (!isset($this->$_aListeners[$name])) {
			$this->$_aListeners[$name] = array();
		}
		
		$this->$_aListeners[$name][] = $listener;
	}
	
	/**
	 * 이벤트 리스너 삭제
	 *
	 * @param String $name 이벤트 이름
	 * @param Function $listener 이벤트 리스너, 값이 없으면 모든 리스너를 삭제
	 */
	public function removeEvent($name, $listener = null) {
		if (isset($this->$_aListeners[$name]) && is_array($this->$_aListeners[$name])) {
			if ($listener === null) {
				unset($this->$_aListeners[$name]);
			} else {
				for ($i = 0; $i < $this->$_aListeners[$name]; $i++) {
					if ($this->$_aListeners[$name][$i] == $listener) {
						array_splice($this->$_aListeners[$name], $i, 1);
						break;	
					}				
				}
			}
		}
	}
	
	/**
	 * 이벤트 발생
	 * 
	 * @param String $name 이벤트 이름
	 * @param Array|Object $event 전달할 이벤트 값
	 */
	public function fireEvent($name, $event = null) {
		if (isset($this->$_aListeners[$name]) && is_array($this->$_aListeners[$name])) {
			for ($i = 0, $length = count($this->$_aListeners[$name]); $i < $length; $i++) {
				$this->$_aListeners[$name][$i]($event);
			}
		}
	}
}
?>