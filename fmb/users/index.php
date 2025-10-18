<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
  <div class="card-body">
    <?php if (!empty($values['yearly_hub'])) { 
      if (!empty($_SESSION['thali'])) { ?>
        <div class="row">
          <div class="col-6">
            <h2 class="mb-5">View Menu</h2>
          </div>
          <div class="col-6 text-end">
            <?php if ($values['hardstop'] == 1) { ?>
              <h4>Your thali is currently stopped: <?php echo $values['hardstop_comment']; ?></h4>
            <?php } else { ?>
              <button type="button" class="btn btn-light" data-bs-target="#stop_thali" data-bs-toggle="modal">Stop
                Thali</button>
            <?php } ?>
          </div>
        </div>

        <?php if (isset($_GET['action']) && $_GET['action'] == 'srange') { ?>
          <div class="alert alert-success" role="alert">Your thali is stopped from <strong>
              <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
            </strong> to <strong>
              <?php echo date('d M Y', strtotime($_GET['edate'])); ?>
            </strong>. Click <a href="/fmb/users/stop_dates.php">here</a> to view stopped dates.</div>
        <?php } 
        if (isset($_GET['action']) && $_GET['action'] == 'srsvp') { ?>
          <div class="alert alert-warning" role="alert">RSVP ended to stop thali of <strong>
              <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
            </strong>.</div>
        <?php }  ?>

        <div class="modal fade" id="stop_thali">
          <div class="modal-dialog">
            <div class="modal-content">
              <form id="user_stop" class="form-horizontal" method="post" action="stopthali.php" autocomplete="off">
                <input type="hidden" name="action" value="stop_thali" />
                <input type="hidden" id="thali" name="thali" value="<?php echo $_SESSION['thali']; ?>" />
                <div class="modal-header">
                  <h4 class="modal-title">Stop Thali</h4>
                  <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                      class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                  <div class="input-group input-daterange mb-3">
                    <input type="text" class="form-control" name="start_date" id="start_date" placeholder="Start Date">
                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                    <input type="text" class="form-control" name="end_date" id="end_date" placeholder="End Date">
                  </div>
                  <p class="text-danger mb-0"><strong>Note:</strong> RSVP Will End at 5 PM one day before start date.<p>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-light">Submit</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <?php include('viewmenu.php'); ?>

      <?php } else { ?>
        <h5 class="mb-3">Sabeel no is not assigned yet. Please contact Moiz Bhai Mulla at <a href="https://api.whatsapp.com/send?phone=+919096778753">9096778753</a> to view this page.</h5>
      <?php }
    } else { ?>
      <h5 class="mb-3">You dont see anything here probably because you are not taking barakat of thali or
        dont have a transporter assigned yet.</h5>
    <?php } ?>
  </div>
</div>

<?php include('footer.php'); ?>
