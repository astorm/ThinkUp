<h1>AJAX Queue</h1>
<p id="pulsestorm_ajaxqueue_rows">
    Rows Remaining: <span id="pulsestorm_rows_left">Fetching</blink></span>
</p>
<div id="ajax_queue_error" style="display:none">
    <h2>Error</h2>
    <textarea id="ajax_queue_error_text"> This is ourselves </textarea>
</div>

<p id="pulsestorm_ajaxqueue_observers" style="display:none;">
    Done procesing raw rows.  <a href="crawler/updatenow.php">Capture Data</a> to process users and posts.
</p>

<script type="text/javascript">
    var pulsestorm_ajaxqueue_data = {literal}{{/literal}
        //'url-raw'       :'/?v=crawlqueue&u=3253&n=stackexchange&action=processraw'
        'url-raw'       :'dashboard.php?v=crawlqueue&u={$smarty.get.u|string_format:"%d"}&n=stackexchange&action=processraw'        
    {literal}}{/literal};
</script>

{literal}
<script type="text/javascript">
    jQuery(document).ready(function(){
        var $ = jQuery;
        var data = {};
        var process = function(url)
        {
            var result;
            $(document).ajaxError(function(event, xhttp){                
                $('#pulsestorm_rows_left').html('<span class="error">An Error Occured<\/span> ');
                pulse_dom_id = false;
                alert_message = $(".alert",xhttp.response).first();
                debug = xhttp.response;
                if(alert_message.length > 0)
                {
                    $('#ajax_queue_error').show().html(alert_message);
                    console.log(alert_message);
                    return;
                }
                
                //still here?
                $('#ajax_queue_error').show().html('<textarea id="ajax_queue_error_text"><\/textarea>');
                $('#ajax_queue_error_text').val(xhttp.response);                
            });
            
            result = $.get(url,{},function(result){
                if(result.rowsLeft.length > 0)
                {                    
                    process(url);
                }
                else
                {   
                    pulse_dom_id = false;
                    $('#pulsestorm_ajaxqueue_observers').show();                                    
                    process_observers();
                }
                update_raw(result);
            },'json');        
            
        };        
        
        var process_observers = function()
        {

        };
        
        var update_raw = function(result)
        {
            $('#pulsestorm_rows_left').html(result.rowsLeft.length);
        };
        
        var get_pulse_domid = function()
        {
            return pulse_dom_id;
        };        
        
        var pulse = function()
        {
            var dom_id = get_pulse_domid();
            if(!dom_id)
            {
                return;
            }
            $(dom_id).show();
            $(dom_id).animate({"color": "#aaaaaa"}, "slow", false,function(){
                $(dom_id).animate({"color": "#000000"}, "slow");
                setTimeout(pulse,1000);   //avoid a too deep recursion stack
            });        
        };
        
        var pulse_dom_id = '#pulsestorm_rows_left';
        pulse();              
        process(pulsestorm_ajaxqueue_data['url-raw']);
    });
</script>
{/literal}
