<?php
  include "config/config.php";

  $body  = "<h1> Dashboard</h1><hr class=\"alt\">";

  $body .= '<div><b>CPU Load: </b><span id="cpu_num">-</span></div>';
  $body .= '<div><b>Memory usage: </b><span id="mem_num">-</span></div>';
  $body .= '<div><b>Active jobs: </b><span id="proc_num">-</span></div>';


  $script = "<script>
  function executeQuery() {
    var url = '".$route_root."stat.php';
    $.ajax({
      dataType: 'json',
      url: url,
    }).done(function(data) {
      var cpu = Math.round(data['cpu'] * 100);
      var mem = Math.round(data['mem']);
      var proc = data['proc'];
      var pactive = 0;

      $('#procs').find('tr:gt(0)').remove();
      for(var i=0;i<proc.length;i++) {
        var p = proc[i];
        if(p['active'] != '0') {
          if(pactive == 0) {
            $('#noactive').hide();
          }
          pactive++;
          $('#procs tr:last').after('<tr><td>'+proc[i]['id']+'</td><td>'+p['name']+'</td><td>'+p['status']+'</td></tr>');
        }
      }
      if(pactive==0) {
        $('#noactive').show();
      }

      $('#cpu_num').text(cpu + '%');
      $('#mem_num').text(mem + '%');
      $('#proc_num').text(pactive);
    }).fail(function(data) {
      console.log('Stat Server unavailable.');
    });
    setTimeout(executeQuery, 5000); // you could choose not to continue on failure...
  }
  $(document).ready(function() {
    // run the first time; all subsequent calls will take care of themselves
    setTimeout(executeQuery, 100);
  });
  </script>";

  $body .= "<hr class=\"alt\"><h2> Active jobs</h2>";
  $body .= '<table id="procs" class="table table-striped table-sm"> <tr><th>#</th><th>Name</th><th>Status</th></tr>';
  $body .= '</table>';
  $body .= '<p id="noactive">No active jobs</p>';

  $contents["tab"] = "Dashboard";
  $contents["header"] = "";
  $contents["script"] = $script;
  $contents["body"] = $body;
  // draw template
  include 'templates/template.main.php';

?>
