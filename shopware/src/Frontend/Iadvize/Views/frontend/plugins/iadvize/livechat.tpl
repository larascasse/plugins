{block name="frontend_index"}
	{if $sTransactionId}
		{include file='frontend/plugins/iadvize/tracking.tpl'}
	{/if}
	<!-- START IADVIZE LIVECHAT -->
		<script type="text/javascript">
			(function() {
			var idz = document.createElement('script'); idz.type = 'text/javascript'; idz.async = true;
			idz.src = document.location.protocol + '//lc.iadvize.com/iadvize.js?sid={$iAdvizeId}';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(idz, s);
			})();
		</script>
	<!-- END IADVIZE LIVECHAT -->
{/block}