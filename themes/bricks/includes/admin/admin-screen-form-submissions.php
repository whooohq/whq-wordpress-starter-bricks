<?php
namespace Bricks;

use Bricks\Integrations\Form\Submission_Table;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$submission_table = new Submission_Table();
$submission_table->prepare_items();
?>

<div class="wrap form-submission">
	<h1 class="wp-heading-inline">
		<?php $submission_table->display_page_title(); ?>
	</h1>

	<hr class="wp-header-end">

	<form id="bricks-form-submissions" method="post">
		<div class="actions">
			<?php $submission_table->display_top_bar(); ?>
		</div>

		<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />

		<div class="wp-list-table-container">
			<?php $submission_table->display(); ?>
		</div>
	</form>
</div>
