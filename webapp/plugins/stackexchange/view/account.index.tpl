<div class="append_20 alert helpful">
    {insert name="help_link" id='stackexchange'}
    <h2>StackExchange</h2>
    <div>
    <p>{$message}</p>
    </div>
</div>

    {if $oauthorize_link}
        <div id="add-account-div" style="padding-top : 20px;">
        <a href="{$oauthorize_link}" class="linkbutton emphasized">Add a Stack Exchange Account</a>
        <br /><br />
        </div>
    {/if}
    
    {if count($owner_instances) > 0 }
    <br>
    <h2 class="subhead">Stack Exchange Accounts</h2>
        <div class="clearfix">
            <div class="grid_4 right" style="padding-top:.5em;">
                Account ID
            </div>    
        </div>
        {foreach from=$owner_instances key=iid item=i name=foo}
            <div class="clearfix">
                <div class="grid_4 right" style="padding-top:.5em;">
                    <a href="{$site_root_path}?u={$i->network_username}">{$i->network_username}</a>
                </div>
                <div class="grid_4 right">
                    <span id="div{$i->id}"><input type="submit" name="submit" class="linkbutton
                    {if $i->is_public}btnPriv{else}btnPub{/if}" id="{$i->id}" value="{if $i->is_public}set private{else}set public{/if}" /></span>
                </div>
                <div class="grid_4 right">
                    <span id="divactivate{$i->id}"><input type="submit" name="submit" class="linkbutton {if $i->is_active}btnPause{else}btnPlay{/if}" id="{$i->id}" value="{if $i->is_active}pause crawling{else}start crawling{/if}" /></span>
                </div>
                <div class="grid_8 right">
                    <span id="delete{$i->id}"><form method="post" action="{$site_root_path}account/?p={$network}">
                    <input type="hidden" name="instance_id" value="{$i->id}">
                    {insert name="csrf_token"}<input
                    onClick="return confirm('Do you really want to delete this Stack Exchange account?');"
                    type="submit" name="action" class="linkbutton" 
                    value="delete" /></form></span>
                </div>
            </div>
            <div class="clearfix">
                <div style="padding-top:.5em;">
                    Member Of: 
                    {foreach from=$i->member_of item=j}
                    {$j}, 
                    {/foreach}
                </div>
            </div>
        {/foreach}
    {/if}
    
    
    <div class="prepend_20 append_20">
    
    {if $options_markup}
        {if $user_is_admin}
            {include file="_plugin.showhider.tpl"}
                    
            {include file="_usermessage.tpl" field="setup"}
                    
            <p style="padding:5px">To set up the StackExchange plugin:</p>
            <ol style="margin-left:40px">
                <li><a href="http://stackapps.com/apps/oauth/register" target="_blank" style="text-decoration: underline;">Create a new application on StackExchange for ThinkUp</a>.</li>
    
                <li>
                    Fill in the following settings.<br />
                    Name: <span style="font-family:Courier;">ThinkUp</span><br />
                    Description: <span style="font-family:Courier;">My ThinkUp installation</span><br />
                    Website: 
                    <small>
                      <code style="font-family:Courier;" id="clippy_2987">{$thinkup_site_url}</code>
                    </small>
                    <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
                              width="100"
                              height="14"
                              class="clippy"
                              id="clippy" >
                      <param name="movie" value="{$site_root_path}assets/flash/clippy.swf"/>
                      <param name="allowScriptAccess" value="always" />
                      <param name="quality" value="high" />
                      <param name="scale" value="noscale" />
                      <param NAME="FlashVars" value="id=clippy_2987&amp;copied=copied!&amp;copyto=copy to clipboard">
                      <param name="bgcolor" value="#FFFFFF">
                      <param name="wmode" value="opaque">
                      <embed src="{$site_root_path}assets/flash/clippy.swf"
                             width="100"
                             height="14"
                             name="clippy"
                             quality="high"
                             allowScriptAccess="always"
                             type="application/x-shockwave-flash"
                             pluginspage="http://www.macromedia.com/go/getflashplayer"
                             FlashVars="id=clippy_2987&amp;copied=copied!&amp;copyto=copy to clipboard"
                             bgcolor="#FFFFFF"
                             wmode="opaque"
                      />
                    </object>
                    <br />
                    Callback URL:
                    <small>
                      <code style="font-family:Courier;" id="clippy_2988">{$thinkup_site_url}account/?p=stackexchange</code>
                    </small>
                    <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
                              width="100"
                              height="14"
                              class="clippy"
                              id="clippy" >
                      <param name="movie" value="{$site_root_path}assets/flash/clippy.swf"/>
                      <param name="allowScriptAccess" value="always" />
                      <param name="quality" value="high" />
                      <param name="scale" value="noscale" />
                      <param NAME="FlashVars" value="id=clippy_2988&amp;copied=copied!&amp;copyto=copy to clipboard">
                      <param name="bgcolor" value="#FFFFFF">
                      <param name="wmode" value="opaque">
                      <embed src="{$site_root_path}assets/flash/clippy.swf"
                             width="100"
                             height="14"
                             name="clippy"
                             quality="high"
                             allowScriptAccess="always"
                             type="application/x-shockwave-flash"
                             pluginspage="http://www.macromedia.com/go/getflashplayer"
                             FlashVars="id=clippy_2988&amp;copied=copied!&amp;copyto=copy to clipboard"
                             bgcolor="#FFFFFF"
                             wmode="opaque"
                      />
                    </object>
                </li>
                <li>Set the application Default Access type to "Read-only".</li>
                <li>Enter the StackExchange-provided consumer key and secret here.</li></ol>            
            </ol>
            
            {$options_markup}
        {/if}
    {/if}
    </div>

