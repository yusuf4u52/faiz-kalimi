<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');

$isEventAdmin = user_email_in(EVENT_NOT_REGISTERED_VIEWER_EMAILS);
?>
<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Event Registration</h2>
		<div class="modal" id="modal">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<form method="post" action="event_add_friend.php">
						<div class="modal-header">
							<h5 class="modal-title">Add a friend</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<fieldset>
								<div class="form-group">
									<label>ITS</label>
									<input type="text" class="form-control" name="its" required
										placeholder="Enter ITS" pattern="[0-9]{8}">
								</div>
								<div class="form-group">
									<label>Full Name</label>
									<input type="text" class="form-control" name="name" required
										placeholder="Enter Full Name">
								</div>
								<div class="form-group">
									<label>Mobile</label>
									<input type="text" class="form-control" name="mobile" required
										placeholder="Enter Mobile" pattern="[0-9]{10}">
								</div>
								<input type="hidden" name="reference_id" id="add_friend_refid">
								<input type="hidden" name="eventid" id="add_friend_eventid">
							</fieldset>
						</div>
						<div class="modal-footer">
							<button type="submit" class="btn btn-light">Save changes</button>
							<button type="button" class="btn btn-secondary"
								data-dismiss="modal">Close</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<table class="table table-striped" width="100%">
			<thead>
				<tr>
					<th>Name</th>
					<th>Details</th>
					<th>Response</th>
					<th>Action</th>
					<?php if ($isEventAdmin) { ?>
						<th>Admin</th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php
				// BUG FIX: the original WHERE clause was
				//   Transporter is not null and Active in (0,1) and Email_id = ? OR SEmail_ID = ?
				// Without parentheses, SQL's AND binds tighter than OR, so this
				// actually evaluated as
				//   (Transporter is not null and Active in (0,1) and Email_id = ?) OR (SEmail_ID = ?)
				// meaning a SEmail_ID match alone counted as "takes FMB" even for
				// an inactive thali with no transporter. Explicit parens fix it.
				$takesFmbResult = db_query(
					$link,
					"SELECT id FROM thalilist WHERE Transporter IS NOT NULL AND Active IN (0, 1) AND (Email_id = ? OR SEmail_ID = ?)",
					"ss",
					[$_SESSION['email'], $_SESSION['email']]
				);
				$takesFmb = mysqli_num_rows($takesFmbResult);

				$result = db_query($link, "SELECT * FROM events WHERE showonpage = 1 ORDER BY id");
				while ($values = mysqli_fetch_assoc($result)) {
					$response = getResponse($values['id']);
					$showToNonFmbOnly = $values['showtononfmb'];
					// skip events for fmb holders if the database flag is set to do so
					// BUG FIX: this used to `exit;` here, which — since this is
					// mid-loop, mid-<table> — would cut off the rest of the page
					// entirely (remaining events, </table>, footer, and the JS
					// block at the bottom) the moment it hit the first
					// FMB-holder-only event. `continue;` skips just this one event.
					if ($showToNonFmbOnly == 0 && $takesFmb == 0) {
						continue;
					}
					?>
					<tr>
						<th scope="row"><?php echo e($values['name']); ?></th>
						<td><?php echo e($values['venue']); ?></td>
						<?php echo isResponseReceived($values['id']) ? '<td>You Said ["' . e($response['response'] ?? '') . '"]</td>' : '<td>No Response</td>'; ?>
						<td>
							<button type="button" <?php echo $values['enabled'] == 0 ? 'disabled' : ''; ?>
								data-eventid="<?php echo (int) $values['id']; ?>"
								data-thaliid="<?php echo e($_SESSION['thaliid'] ?? ''); ?>" data-response="yes"
								class="btn btn-light btn-sm btn-response me-2 mb-2 action-<?php echo (int) $values['id']; ?>">Yes</button>
							<button type="button" <?php echo $values['enabled'] == 0 ? 'disabled' : ''; ?>
								data-eventid="<?php echo (int) $values['id']; ?>"
								data-thaliid="<?php echo e($_SESSION['thaliid'] ?? ''); ?>" data-response="no"
								class="btn btn-light btn-sm mb-2 btn-response action-<?php echo (int) $values['id']; ?>">No</button>
						</td>
						<?php if ($isEventAdmin) { ?>
							<td><a href="event_get_not_registered_users.php?eventid=<?php echo (int) $values['id']; ?>">Not Registered</a></td>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>

<?php include('footer.php'); ?>

<script>
	$(document).ready(function () {
		$(".btn-response").click(function () {
			$(".action-" + $(this).data("eventid")).attr("disabled", true);
			$.ajaxSetup({
				beforeSend: function (xhr) {
					xhr.setRequestHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)');
				}
			});
			$.post("event_response.php", {
				Response: $(this).data("response"),
				Thaliid: $(this).data("thaliid"),
				Eventid: $(this).data("eventid"),
				Comments: $('textarea#comments').val(),
				Thalisize: $('input[name=' + $(this).data("eventid") + 'optionsRadios]:checked').val()
			},
				function (data, status) {
					alert("Response Submitted Successfully");
					window.location.reload();
				});
		});

		$(".add_friend").click(function () {
			$('#add_friend_refid').val($(this).data('thaliid'));
			$('#add_friend_eventid').val($(this).data('eventid'));
			$("#modal").modal();
		});
	});
</script>
