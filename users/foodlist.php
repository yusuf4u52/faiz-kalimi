<?php
include('connection.php');
include('_authCheck.php');

$result = mysqli_query($link, "SELECT * FROM food_list order by `dish_name` ASC") or die(mysqli_error($link));

?>

<html>

<head>
    <?php include('_head.php'); ?>
</head>

<body>
    <?php include('_nav.php'); ?>
    <div class="container">
        <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
            <div class="alert alert-success" role="alert"><strong><?php echo $_GET['dish']; ?></strong> added successfully.</div>
        <?php } ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
            <div class="alert alert-success" role="alert"><strong><?php echo $_GET['dish']; ?></strong> edited successfully.</div>
        <?php } ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
            <div class="alert alert-success" role="alert"><strong><?php echo $_GET['dish']; ?></strong> deleted successfully.</div>
        <?php } ?>
        <button type="button" class="btn btn-primary" data-target="#addfood" data-toggle="modal">Add Food Item</button><br><br>
        <table class="table table-striped table-hover" id="my-table">
            <thead>
                <tr>
                    <th width="50%">Dish Name</th>
                    <th>Dish Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($values = mysqli_fetch_assoc($result)) { 
                    if( $values['dish_type'] == '1' ) {
                        $dish_type = 'Sabji Item';
                    } elseif( $values['dish_type'] == '2' ) {
                        $dish_type = 'Tarkari/Dal Item';
                    } elseif( $values['dish_type'] == '3' ) {
                        $dish_type = 'Rice Item';
                    } elseif( $values['dish_type'] == '4' ) {
                        $dish_type = 'Roti/Bread Item';
                    } elseif( $values['dish_type'] == '5' ) {
                        $dish_type = 'Extra Item';
                    } else {
                        $dish_type = '';
                    } ?>
                    <tr>
                        <td><?php echo $values['dish_name']; ?></td>
                        <td><?php echo $dish_type; ?></td>
                        <td><button type="button" class="btn btn-success" data-target="#editfood-<?php echo $values['id']; ?>" data-toggle="modal" style="margin-bottom:5px"><i class="fas fa-edit"></i></button> <button type="button" class="btn btn-danger" data-target="#deletefood-<?php echo $values['id']; ?>" data-toggle="modal" style="margin-bottom:5px"><i class="fas fa-trash"></i></button></td>
                    </tr>
                <?php }
                mysqli_free_result($result); ?>
            </tbody>
        </table>
    </div>

    <?php $result = mysqli_query($link, "SELECT * FROM food_list order by `dish_name` ASC") or die(mysqli_error($link));
    while ($values = mysqli_fetch_assoc($result)) { ?>
        <div class="modal" id="editfood-<?php echo $values['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editfood-<?php echo $values['id']; ?>" class="form-horizontal" method="post" action="savefood.php">
                        <input type="hidden" name="action" value="edit_food" />
                        <input type="hidden" name="food_id" value="<?php echo $values['id']; ?>" />
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Edit Food Item</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group row">
                                <label for="dish_name" class="col-xs-4 control-label">Dish Name</label>
                                <div class="col-xs-8">
                                    <input type="text" class="form-control" name="dish_name" value="<?php echo $values['dish_name']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="dish_type" class="col-xs-4 control-label">Dish Type</label>
                                <div class="col-xs-8">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dish_type" id="dish_type1" value="1" <?php echo (( $values['dish_type'] == '1') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="dish_type1">Sabji Item</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dish_type" id="dish_type2" value="2" <?php echo (( $values['dish_type'] == '2') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="dish_type2">Tarkari/Dal Item</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dish_type" id="dish_type3" value="3" <?php echo (( $values['dish_type'] == '3') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="dish_type3">Rice Item</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dish_type" id="dish_type4" value="4" <?php echo (( $values['dish_type'] == '4') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="dish_type4">Roti/Bread Item</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dish_type" id="dish_type5" value="5" <?php echo (( $values['dish_type'] == '5') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="dish_type5">Extra Item</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } mysqli_free_result($result); ?>

    <?php $result = mysqli_query($link, "SELECT * FROM food_list order by `dish_name` ASC") or die(mysqli_error($link));
    while ($values = mysqli_fetch_assoc($result)) { ?>
        <div class="modal" id="deletefood-<?php echo $values['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="deletefood-<?php echo $values['id']; ?>" class="form-horizontal" method="post" action="savefood.php">
                        <input type="hidden" name="action" value="delete_food" />
                        <input type="hidden" name="dish_name" value="<?php echo $values['dish_name']; ?>" />
                        <input type="hidden" name="food_id" value="<?php echo $values['id']; ?>" />
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Delete Food Item</h4>
                        </div>
                        <div class="modal-body">
                            <p> Are you sure you want to delete <strong><?php echo $values['dish_name']; ?></strong> from database ? </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } mysqli_free_result($result); ?>

    <div class="modal" id="addfood">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addfood" class="form-horizontal" method="post" action="savefood.php">
                    <input type="hidden" name="action" value="add_food" />
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Add Food Item</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row">
                            <label for="dish_name" class="col-xs-4 control-label">Dish Name</label>
                            <div class="col-xs-8">
                                <input type="text" class="form-control" name="dish_name" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="dish_pack" class="col-xs-4 control-label">Dish Type</label>
                            <div class="col-xs-8">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dish_type" id="dish_type1" value="1">
                                    <label class="form-check-label" for="dish_type1">Sabji Item</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dish_type" id="dish_type2" value="2">
                                    <label class="form-check-label" for="dish_type2">Tarkari/Dal Item</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dish_type" id="dish_type3" value="3">
                                    <label class="form-check-label" for="dish_type3">Rice Item</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dish_type" id="dish_type4" value="4">
                                    <label class="form-check-label" for="dish_type4">Roti/Bread Item</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dish_type" id="dish_type5" value="5">
                                    <label class="form-check-label" for="dish_type5">Extra Item</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('_bottomJS.php'); ?>
    <script type="text/javascript">
        $('#my-table').dynatable();
    </script>
</body>

</html>
