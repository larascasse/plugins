{block name="frontend_index_header_javascript" append}

{if $sTransactionId}
	<!-- START IADVIZE CONVERSION TRACKING CODE -->
	<script type="text/javascript">
		var idzTrans = {literal}{{/literal}'tID':'{$sTransactionId}','cartAmount':{$sCartAmount}{literal}}{/literal};
	</script>
	<!-- END IADVIZE CONVERSION TRACKING CODE -->
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