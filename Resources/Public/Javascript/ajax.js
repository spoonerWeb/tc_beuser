function updateData(node, method, id, open) {
	var data = 'ajaxCall=1&method=' + method + '&groupId=' + id + '&open=' + open + '&backPath=' + T3_BACKPATH;

	var myAjax = new Ajax.Updater(
		node.parentNode.id,
		ajaxUrl,
		{
			method: 'post',
			parameters: data
		}
	);
}
