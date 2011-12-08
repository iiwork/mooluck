/**
 * document.body에 Event Delegation을 관리하는 Manager
 * jQuery 1.6 이상에서만 사용할 수 있는 namespace 사용 
 *
 * @class EventManager
 */
ii.EventManager = function () {
	var _body = $(document.body);
	var _loaded = false;
	var _queue = [];
	var EVENT_NAMESPACE = '.iiEventManager';
	
	/**
	 * Event Binding
	 * 
	 * @param {String} selector
	 * @param {String} eventType
	 * @param {Function} callback
	 */
	this.bind = function (selector, eventType, callback) {
		if (!_loaded) {
			_queue.push([selector, eventType, callback]);
		} else {
			eventType += EVENT_NAMESPACE;
			_body.delegate(selector, eventType, callback);
		}
	};
	
	/**
	 * 활성화
	 */
	this.load = function () {
		_loaded = true;
		
		// queue에 쌓여있는 bind가 있을 경우 실행하고 비움
		if (_queue.length) {
			for (var i = 0; i < _queue.length; i++) {
				_body.delegate.apply(_body, _queue[i]);
			}
			
			_queue = [];
		}
	};
	
	/**
	 * 비활성화 
	 */
	this.unload = function () {
		_body.undelegate(EVENT_NAMESPACE);
		_queue = [];
	};
};