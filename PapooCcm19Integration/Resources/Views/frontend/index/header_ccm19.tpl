{extends file='parent:frontend/index/header.tpl'}

{* Integrate CCM19 before any other script *}
{block name="frontend_index_header_meta_http_tags"}
	{if $ccm19IntegrationUrl}
	<!-- BEGIN CCM19 Cookie Consent Management -->
	<script src="{$ccm19IntegrationUrl|escape}" referrerpolicy="origin"></script>
	<!-- END CCM19 -->
	{/if}
	{$smarty.block.parent}
{/block}
