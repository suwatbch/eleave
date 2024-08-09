function initEleaveLeave() {
  var num_days = 0,
    doLeaveTypeChanged = function () {
      /*console.log('leave_id');*/
      if (($G('id').value == 0) || ($G('start_time').value == '00:00' && $G('end_time').value == '00:00')) {
        $G('start_time').value = '';
        $G('end_time').value = '';
      }
      send(WEB_URL + 'index.php/eleave/model/leave/datas', 'id=' + $E('leave_id').value, function (xhr) {
        var maxDate, ds = xhr.responseText.toJSON();
        if (ds) {
          $G('leave_detail').innerHTML = ds.detail.unentityify();
          if ($E('id').value == 0) {
            num_days = ds.num_days;
            var start_date = $G('start_date').value;
            if (num_days == 0) {
              maxDate = null;
            } else if (start_date != '') {
              maxDate = new Date(start_date).moveDate(num_days - 1);
            }
            $G('end_date').max = maxDate;
            $G('end_date').min = start_date;
          }
        } else if (xhr.responseText != '') {
          console.log(xhr.responseText);
        }
      });
    };

  $G('leave_id').addEvent('change', doLeaveTypeChanged);
  doLeaveTypeChanged.call(this);

  $G('leave_id').addEvent("change", function () {
    if (this.value && $E('id').value == 0) {
      /*console.log('leave_id2');*/
      var a = this.value.toInt();
      if (a == 3 || a == 7 || a == 8) {
        $E('start_period').value = 0;
        $E('start_period').disabled = 1;
        $E('end_date').disabled = 0;
        $E('start_time').disabled = 1;
        $E('end_time').disabled = 1;
      } else if ($E('id').value == 0) {
        $E('start_period').disabled = 0;
      }
    }
  });

  $G('start_period').addEvent("change", function () {
    if (this.value && $E('id').value == 0) {
      /*console.log('start_period');*/
      var a = this.value.toInt();
      $E('start_time').disabled = a == 0;
      $E('end_time').disabled = a == 0;
      $E('end_date').disabled = a;
      $E('end_date').value = $E('start_date').value;
      if (!a) {
        $G('start_time').value = '';
        $G('end_time').value = '';
      }
    }
  });

  $G('start_date').addEvent("change", function () {
    if (this.value && $E('id').value == 0) {
      /*console.log('start_date');*/
      if (new Date(this.value) < new Date($G('end_date').value)){
        $G('end_date').min = this.value;
        $G('end_date').value = this.value;
      }
      else
      {
        $G('end_date').value = this.value;
        $G('end_date').min = this.value;
      }

      $G('last_start_date').value = this.value;

      if (num_days > 0) {
        var maxDate = new Date(this.value).moveDate(num_days - 1);
        $G('end_date').max = maxDate;
      }
    } else if ($E('id').value == 0) {
      /*console.log('start_date');*/
      var now = new Date();
      var previousMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      $G('start_date').min = previousMonth.toISOString().split('T')[0];
      $G('start_date').value = $G('last_start_date').value;
    }
  });

  $G('end_date').addEvent("change", function () {
    if (!this.value && $E('id').value == 0) {
      /*console.log('end_date');*/
      $G('end_date').value = $G('start_date').value;
    }
  });

  var elements = [$G('leave_id'),$G('start_period'),$G('start_date'),$G('end_date'),$G('start_time'),$G('end_time')];
  elements.forEach(function(element) {
    if (element && $E('start_date').value != '' && $E('end_date').value != '') {
      /*console.log('leavealert');*/
      element.addEventListener('change', function() {
          var params = new URLSearchParams({
              'index_id': $E('id').value
              ,'leave_id': $E('leave_id').value
              ,'shift_id': $E('shift_id').value
              ,'member_id': $E('member_id').value
              ,'start_period': $E('start_period').value
              ,'start_date': $E('start_date').value
              ,'end_date': $E('end_date').value
              ,'start_time': $E('start_time').value
              ,'end_time': $E('end_time').value
          }).toString();
          var url = WEB_URL + 'index.php/eleave/model/leave/leavealert?' + params;
          send(url, '', function(xhr) {
              var ds = xhr.responseText.toJSON();
              if (ds) {
                  $G('cal_shift_id').value = ds.shift_id;
                  $G('cal_status').value = ds.status;
                  $G('cal_days').value = ds.days;
                  $G('cal_times').value = ds.times;
                  $G('textalert').value = ds.data;
              } else if (xhr.responseText != '') {
                  console.log(xhr.responseText);
              }
          });
      });
    }
  });

  /*var elements = [$E('start_period'),$E('start_date'),$E('start_time'),$E('end_time')];
  elements.forEach(function(element) {
    if (element) {
      element.addEventListener('change', function() {
        var params = new URLSearchParams({
            'start_time': $E('start_time').value,
            'end_time': $E('end_time').value
        }).toString();
        var url = WEB_URL + 'index.php/eleave/model/leave/calculateDuration?' + params;
        send(url, '', function(xhr) {
            var ds = xhr.responseText.toJSON();
            if (ds) {
                console.log(ds.data);
            } else if (xhr.responseText !== '') {
                console.log(xhr.responseText);
            }
        });
      });
    }
  });*/

  /*$G('start_time').addEvent("change", function () {
    console.log('start_time');
    if (this.value && $E('start_time').value != '') {
      var params = new URLSearchParams({
          'shift_id': $E('shift_id').value
          ,'start_time': $E('start_time').value
      }).toString();
      var url = WEB_URL + 'index.php/eleave/model/leave/setSelectTimeStart?' + params;
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
  });*/

  /*$G('end_time').addEvent("change", function () {
    console.log('end_time');
    if (this.value && $E('end_time').value != '') {
      if (timeToMinutes($G('end_time').value) < timeToMinutes($G('start_time').value)) {
        $E('start_time').value = subtractMinutes(this.value, 30);
      }
    }
  });*/

}

/*ฟังก์ชันแปลงเวลาในรูปแบบ HH:MM เป็นนาทีทั้งหมด*/
function timeToMinutes(time) {
  var parts = time.split(':');
  return parseInt(parts[0]) * 60 + parseInt(parts[1]);
}

/*ฟังก์ชันลดเวลาไป 30 นาที*/
function subtractMinutes(time, minutesToSubtract) {
  var minutes = timeToMinutes(time);
  minutes -= minutesToSubtract;
  if (minutes < 0) {
      minutes += 24 * 60;
  }
  return minutesToTime(minutes);
}

/*ฟังก์ชันแปลงนาทีทั้งหมดกลับเป็นรูปแบบ HH:MM*/
function minutesToTime(minutes) {
  var hours = Math.floor(minutes / 60);
  var mins = minutes % 60;
  return (hours < 10 ? '0' : '') + hours + ':' + (mins < 10 ? '0' : '') + mins;
}