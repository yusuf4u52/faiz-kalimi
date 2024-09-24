<?php
include('connection.php');
include('_authCheck.php');
if (isset($_GET['stopallthalis'])) {
  $result = mysqli_query($link, "SELECT Thali from  thalilist WHERE Active='1'") or die(mysqli_error($link));
  $data = array();
  while ($values = mysqli_fetch_assoc($result)) {
    $data[] = $values['Thali'];
  }
  $all_thali_as_csv = implode(',', $data);
  if (empty($all_thali_as_csv)) {
    echo "<script>
            alert('No Active thalis found');
            window.location = window.location.pathname;
      </script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head><?php include('_head.php'); ?></head>

<body>

  <?php include('_nav.php'); ?>

  <div class="container">

    <!-- Forms
      ================================================== -->

    <div class="row">

      <div class="col-lg-12">

        <div class="page-header">

          <h2 id="forms">Stop multiple thaalis at once (use carefully). Enter only comma saperated thaali numbers e.g. 1,2,3.</h2>

        </div>
        <div class="col-lg-6">

          <div class="well bs-component">

            <form class="form-horizontal">

              <fieldset>


                <div class="form-group">

                  <label for="thaliNumbers" class="col-lg-2 control-label">Thali No</label>

                  <div class="col-lg-10">

                    <input type="text" class="form-control" id="thaliNumbers" placeholder="Enter comma saperated thaali numbers" value="<?php
                                                                                                                                        if (isset($all_thali_as_csv)) {
                                                                                                                                          echo "$all_thali_as_csv";
                                                                                                                                        }
                                                                                                                                        ?>">
                    <a href="stopMultipleThaalis.php?stopallthalis=true">Fill above with all active thalis</a>
                    <div>
                      <input type="checkbox" class="custom-control-input" id="hardStop" onclick="showCommentBox()">
                      <label class="custom-control-label" for="customCheck1">Check this if you don't want user be able to start.</label>
                    </div>
                    <div>
                      <textarea class="form-control" id="hardStopComment" rows="3" placeholder="Enter your comments here" style="display:none"></textarea>
                    </div>
                  </div>

                </div>
                <div class="form-group">

                  <div class="col-lg-10 col-lg-offset-2">
                    <button type="button" class="btn btn-primary" id="stopThaliButton">Stop Thaali</button>

                  </div>

                </div>

              </fieldset>

            </form>

            <div id="source-button" class="btn btn-primary btn-xs" style="display: none;">&lt; &gt;</div>
          </div>

        </div>
      </div>



      <div class="row">

        <div class="col-lg-6">
        </div>
      </div>

    </div>

  </div>
  <?php include('_bottomJS.php'); ?>
  <script>
    $(function() {
      $('#stopThaliButton').click(function() {
        var regext = /[0-9,KL-]+/;
        if (!regext.test($('#thaliNumbers').val().replace(/(^,)|(,$)/g, ""))) {
          alert('Please enter only numbers and comma.');
          return false;
        }
        var hardStop = $('#hardStop').is(':checked');
        var hardStopComment = $('#hardStopComment').val();
        var thaliNumbers = $('#thaliNumbers').val().replace(/(^,)|(,$)/g, "").split(',');
        thaliNumbers.join(",");
        if (confirm('Stop thaali # ' + thaliNumbers + ' ?')) {
          for (var i = thaliNumbers.length - 1; i >= 0; i--) {
            var thaliNumber = thaliNumbers[i];
            stopThali_admin(thaliNumber, 0, hardStop, hardStopComment);
          }
          $('#thaliNumbers').val('');
          $('#hardStopComment').val('');
          $('#hardStop').prop('checked', false);
          $('#hardStopComment').hide();
        }
      });
    });

    function showCommentBox() {
      // Get the checkbox
      var checkBox = document.getElementById("hardStop");
      // Get the output text
      var text = document.getElementById("hardStopComment");
      // If the checkbox is checked, display the output text
      if (checkBox.checked == true) {
        text.style.display = "block";
      } else {
        text.style.display = "none";
      }
    }
  </script>

</body>

</html>