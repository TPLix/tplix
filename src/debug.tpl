<style>
.Tplix-Console {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #fff;
  color: #222;
  border-top: 2px solid #ccc;
  font-family: monospace;
  font-size: 13px;
  z-index: 999999;
  box-shadow: 0 -2px 5px rgba(0,0,0,0.15);
  transition: height 0.3s ease;
  overflow: hidden;
}

.Tplix-Console.collapsed {
  height: 40px;
}

.Tplix-Console.expanded {
  height: 90%;
  overflow: auto;
}

.Tplix-header {
  position: relative;
  height: 40px;
  line-height: 40px;
  background: #f1f1f1;
  border-top: 2px solid #ccc;
  padding: 0 10px;
  cursor: pointer;
  font-weight: bold;
}

.Tplix-body {
  display: none;
  padding: 10px;
}

.Tplix-Console.expanded .Tplix-body {
  display: block;
}

    .Tplix-Console h1 {
        margin: 0;
        text-align: left;
        padding: 2px;
        background-color: #f0c040;
        color: black;
        font-weight: bold;
        font-size: 1.2em;
    }

    .Tplix-Console h2 {
        background-color: #9B410E;
        color: white;
        text-align: left;
        font-weight: bold;
        padding: 2px;
        border-top: 1px solid black;
    }

    .Tplix-Console h3 {
        text-align: left;
        font-weight: bold;
        color: black;
        font-size: 0.7em;
        padding: 2px;
    }

    .Tplix-Console p, .Tplix-Console table {
        background: #f0ead8;
    }

    .Tplix-Console p {
        margin: 0;
        font-style: italic;
        text-align: center;
    }

    .Tplix-Console table {
        width: 100%;
    }

    .Tplix-Console th, .Tplix-Console td {
        font-family: monospace;
        vertical-align: top;
        text-align: left;
    }

    .Tplix-Console td {
        color: green;
    }

    .Tplix-Console tr:nth-child(odd) {
        background-color: #eeeeee;
    }

    .Tplix-Console tr:nth-child(even) {
        background-color: #fafafa;
    }

    .Tplix-Console .exectime {
        font-size: 0.8em;
        font-style: italic;
    }

    .Tplix-Console #bold div {
        color: black;
        font-weight: bold;
    }

    .Tplix-Console #blue h3 {
        color: blue;
    }

    .Tplix-Console #normal div {
        color: black;
        font-weight: normal;
    }

    .Tplix-Console #table_assigned_vars th {
        color: blue;
        font-weight: bold;
    }

    .Tplix-Console #table_config_vars th {
        color: maroon;
    }
/* Base alert box */
.tplix-alert {
  position: relative;
  padding: 10px 15px;
  margin: 10px 0;
  border: 1px solid transparent;
  border-radius: 4px;
  font-family: Arial, sans-serif;
  font-size: 14px;
}

/* Info alert */
.tplix-alert-info {
  color: #055160;
  background-color: #cff4fc;
  border-color: #b6effb;
}

/* Danger (error) alert */
.tplix-alert-danger {
  color: #842029;
  background-color: #f8d7da;
  border-color: #f5c2c7;
}

/* Success alert */
.tplix-alert-success {
  color: #0f5132;
  background-color: #d1e7dd;
  border-color: #badbcc;
}

/* Warning alert */
.tplix-alert-warning {
  color: #664d03;
  background-color: #fff3cd;
  border-color: #ffecb5;
}

/* Optional close button */
.tplix-alert .tplix-close {
  position: absolute;
  top: 8px;
  right: 10px;
  background: transparent;
  border: none;
  font-size: 18px;
  line-height: 1;
  cursor: pointer;
  color: inherit;
}    
</style>
<script>
function TplixToggle() {
  const el = document.getElementById('TplixConsole');
  el.classList.toggle('expanded');
  el.classList.toggle('collapsed');
}
</script>
    <div class="Tplix-Console collapsed" id="TplixConsole">
    <div class="Tplix-header" onclick="TplixToggle()">Tplix {$version} Debug Console (click to expand)</div>
    <div class="Tplix-body">
    <h1>Total Time {$execution_time|string_format:"%.5f"} and Total Variables:{$total_vars_count}</h1>
    {if !empty($template_data)}
        <h2>included templates &amp; config files (load time in seconds)</h2>
        <div>
            {foreach $template_data as $template}
                <span style="color: brown;">{$template.name}</span>
                <br>&nbsp;&nbsp;<span class="exectime">
                (compile {$template['compile_time']|string_format:"%.5f"}) (render {$template['render_time']|string_format:"%.5f"}) (cache {$template['cache_time']|string_format:"%.5f"})
                 </span>
                <br>
            {/foreach}
        </div>
    {/if}
    
    <h2>assigned template variables - Total Vars: {$assigned_vars_count}</h2>
    <div class="tplix-alert tplix-alert-info">You can access tplVar variables using <code>$.</code>, as if you want to access time then $.now</div>
    <table id="table_assigned_vars">
    <thead>
        <tr><th>Key</th><th>Origin</th><th>Value</th></tr>
    </thead>
    <tbody>    
        {foreach $assigned_vars as $vars}
            <tr>
                <td>${$vars@key}</td>
                <td>{if isset($vars.scope)}{$vars['scope']}{/if}</td>
                <td>{$vars['value']|debug_print_var:10:80}</td>
                <td>
                    {if isset($vars.attributes)}
                        <h3>Attributes</h3>
                        {$vars['attributes']|debug_print_var}
                    {/if}
                </td>
         {/foreach}
    </table>
    <h2>assigned config file variables</h2>

    <table id="table_config_vars">
    <thead>
        <tr><th>Key</th><th>Origin</th><th>Value</th></tr>
    </thead>
    <tbody>
        {foreach $config_vars as $vars}
            <tr>
                <td>#{$vars@key}#</td>
                <td>{if isset($vars.scope)}{$vars['scope']}{/if}</td>
                <td>{$vars['value']|debug_print_var:10:80}</td>
            </tr>
        {/foreach}
    </tbody>
    </table>
    {if !empty($globals)}
    <h2>Super globalvariables</h2>
    <div class="tplix-alert tplix-alert-info">You can access global variables using <code>$.</code>, as if you want to access get variable then $.get.page</div>
    <table id="table_assigned_vars">
    <thead>
        <tr><th>Key</th><th>Origin</th><th>Value</th></tr>
    </thead>
    <tbody>    
        {foreach $globals as $vars}
            <tr>
                <td>$.{$vars@key}</td>
                <td>{if isset($vars['scope'])}{$vars['scope']}{/if}</td>
                <td>{$vars['value']|debug_print_var:10:80}</td>
         {/foreach}
    </table>
    {/if}
</div>
</div>