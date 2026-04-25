<?php
/**
 * Output a list of roles belonging to the current user.
 *
 * @var $roles array All applicable roles in name => label pairs.
 *
 * @package MultipleRoles
 */

?><div class="md-multiple-roles">
  <?php if ( ! empty( $roles ) ) : ?>
		<?php $it = new CachingIterator( new ArrayIterator( $roles ) ); ?>
		<?php foreach ( $it as $name => $label ) : ?>
	  <a href="users.php?role=<?php echo esc_attr( $name ); ?>"><?php echo esc_html( translate_user_role( $label ) ); ?></a><?php echo $it->hasNext() ? ',' : ''; ?>
	<?php endforeach; ?>
  <?php else : ?>
	<span class="md-multiple-roles-no-role"><?php esc_html_e( 'None', 'multiple-roles' ); ?></span>
  <?php endif; ?>
</div><!-- .md-multiple-roles -->
