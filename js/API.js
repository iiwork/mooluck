ii.Ajax = function () {
	// 기본 설정 변경
	$.ajaxSetup({
		dataType: 'json'
	});
	
	/**
	 * Ajax 요청
	 * @param option $.ajax
	 * @returns
	 */
	this.send = function (option) {
		if (!('error' in option)) {
			option.error = this._onError;
		}
		
		option.success = $.proxy(this._onSuccess, {
			success : option.success,
			error : option.error
		});
		
		$.ajax(option);
	};
	
	/**
	 * 
	 */
	this._onSuccess =  function (data) {
		if (typeof data !== "object") {
			this.error(data);
			return;
		}
		
		if ('error' in data) {
			this.error(data.error.msg, data.error.code, data.error.data);
		} else if ('result' in data) {
			this.success(data.result);
		} else {
			throw new Error("올바르지 않은 API 입니다");
		}
	};

	/**
	 * 기본 에러 핸들러
	 */	
	this._onError =  function () {
		console.alert(arguments);
	};
};