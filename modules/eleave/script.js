function initEleaveLeave() {
  var num_days = 0,
    doLeaveTypeChanged = function () {
      send(WEB_URL + 'index.php/eleave/model/leave/datas', 'id=' + $E('leave_id').value, function (xhr) {
        var maxDate, ds = xhr.responseText.toJSON();
        if (ds) {
          $G('leave_detail').innerHTML = ds.detail.unentityify();
          num_days = ds.num_days;
          var start_date = $G('start_date').value;
          if (num_days == 0) {
            maxDate = null;
          } else if (start_date != '') {
            maxDate = new Date(start_date).moveDate(num_days - 1);
          }
          $G('end_date').max = maxDate;
          $G('end_date').min = start_date;
        } else if (xhr.responseText != '') {
          console.log(xhr.responseText);
        }
      });
    };
  $G('leave_id').addEvent('change', doLeaveTypeChanged);
  doLeaveTypeChanged.call(this);
  $G('start_date').addEvent("change", function () {
    if (this.value) {
      $G('end_date').value = this.value;
      $G('end_date').min = this.value;
      if (num_days > 0) {
        var maxDate = new Date(this.value).moveDate(num_days - 1);
        $G('end_date').max = maxDate;
      }
    }
  });
  $G('start_period').addEvent("change", function () {
    if (this.value) {
      var a = this.value.toInt();
      $E('start_time').disabled = a == 0;
      $E('end_time').disabled = a == 0;
      $E('end_date').disabled = a;
      $E('end_date').value = $E('start_date').value;
      
    }
  });
  $G('leave_id').addEvent("change", function () {
    if (this.value) {
      var a = this.value.toInt();
      if (a == 3 || a == 7 || a == 8) {
        $E('start_period').value = 0;
        $E('start_period').disabled = 1;
        $E('end_date').disabled = 0;
        $E('start_time').disabled = 1;
        $E('end_time').disabled = 1;

      } else {
        $E('start_period').disabled = 0;
      }
    }
  });

  $G('start_time').addEvent("change", function () {
    if (this.value && $E('start_time').value != '') {
      var params = new URLSearchParams({
          'shift_id': $E('shift_id').value
          ,'start_time': $E('start_time').value
      }).toString();
      var url = WEB_URL + 'index.php/eleave/model/leave/setSelectTime?' + params;
      send(url, '', function(xhr) {
          var ds = xhr.responseText.toJSON();
          if (ds) {
              console.log(ds.leave_end_time);
              $E('end_time').value = ds.end_time;
          } else if (xhr.responseText != '') {
              console.log(xhr.responseText);
          }
      });
    }
  });

  /*var elements = [$E('leave_id'),$E('start_date'),$G('end_date'),$E('start_time'),$G('end_time'),$G('start_period')];
  elements.forEach(function(element) {
    if (element && $E('start_date').value != '' && $E('end_date').value != '') {
      element.addEventListener('change', function() {
          var params = new URLSearchParams({
              'leave_id': $E('leave_id').value
              ,'shift_id': $E('shift_id').value
              ,'username': $E('username').value
              ,'start_period': $E('start_period').value
              ,'start_date': $E('start_date').value
              ,'start_time': $E('start_time').value
              ,'end_date': $E('end_date').value
              ,'end_time': $E('end_time').value
          }).toString();
          
          var url = WEB_URL + 'index.php/eleave/model/leave/leavealert?' + params;
          
          send(url, '', function(xhr) {
              var ds = xhr.responseText.toJSON();
              if (ds) {
                  $G('textalert').value = ds.data;
              } else if (xhr.responseText != '') {
                  console.log(xhr.responseText);
              }
          });
      });
    }
  });*/

}