var ii = {};

// debug
if (!('console' in window)) {
	window.console = {
		log : function () {},
		alert : function () {
			alert(arguments.join(", "));
		}
	};
}

/**
 * 기본 Util들
 */
(function (ii) {
	var modules = [];
	var names = {};
	var Klass;
	
	/**
	 * Factory 로더
	 */
	ii.get = function (name) {
		if (!(name in ii)) {
			throw new Error('ii namespace에 ' + name + ' Class가 없습니다');
		}
		
		if (name in names) {
			return modules[names[name]];
		} else {
			Klass = eval('new ii.' + name + '()');
			modules.push(Klass);
			names[name] = modules.length - 1;
			return Klass;
		}
	};	
})(ii);