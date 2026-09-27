<?php
/**
 * Title: Tutorial with lessons
 * Slug: kamal-notebook/tutorial-course
 * Description: One long article divided into editable lessons with an automatic lesson outline.
 * Categories: kn-writing, text
 * Block Types: core/post-content
 * Post Types: post
 * Keywords: tutorial, course, series, lessons, guide
 */
?>
<!-- wp:group {"className":"kn-tutorial","layout":{"type":"constrained"}} -->
<div class="wp-block-group kn-tutorial">
<!-- wp:group {"className":"kn-tutorial-intro","layout":{"type":"constrained"}} -->
<div class="wp-block-group kn-tutorial-intro"><!-- wp:paragraph {"className":"kn-tutorial-eyebrow"} --><p class="kn-tutorial-eyebrow">A GUIDED TUTORIAL / THREE LESSONS</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Introduce the project, the starting knowledge readers need, and what they will be able to make or understand by the end. Replace this demonstration text before publishing.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"kn-lesson","layout":{"type":"constrained"}} -->
<div class="wp-block-group kn-lesson"><!-- wp:paragraph {"className":"kn-lesson-label"} --><p class="kn-lesson-label">LESSON 01 / THE FOUNDATION</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">The starting point</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Explain one idea at a time. Add a concrete example before moving to the next lesson.</p><!-- /wp:paragraph --><!-- wp:group {"className":"kn-visual-walkthrough","layout":{"type":"constrained"}} --><div class="wp-block-group kn-visual-walkthrough"><!-- wp:image {"className":"kn-visual-image"} --><figure class="wp-block-image kn-visual-image"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/art-programming.svg' ) ); ?>" alt=""/><figcaption class="wp-element-caption">Replace this illustration with your own screenshot or diagram.</figcaption></figure><!-- /wp:image --><!-- wp:group {"className":"kn-visual-copy","layout":{"type":"constrained"}} --><div class="wp-block-group kn-visual-copy"><!-- wp:paragraph {"className":"kn-visual-label"} --><p class="kn-visual-label">LOOK CLOSER / 01</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Show the moving pieces</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Use a screenshot, annotated image, or diagram and explain what the reader should notice.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"kn-lesson","layout":{"type":"constrained"}} -->
<div class="wp-block-group kn-lesson"><!-- wp:paragraph {"className":"kn-lesson-label"} --><p class="kn-lesson-label">LESSON 02 / THE PRACTICE</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Try the idea</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Walk through a small exercise in order. Insert a Highlighted code example pattern whenever the reader needs real code.</p><!-- /wp:paragraph --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Start with the smallest working example.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Change one part and describe what happens.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Check the result before continuing.</li><!-- /wp:list-item --></ul><!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"kn-lesson","layout":{"type":"constrained"}} -->
<div class="wp-block-group kn-lesson"><!-- wp:paragraph {"className":"kn-lesson-label"} --><p class="kn-lesson-label">LESSON 03 / THE WHOLE PICTURE</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Put it together</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Bring the earlier pieces together. Summarize the method and point to a sensible next experiment.</p><!-- /wp:paragraph --><!-- wp:group {"className":"kn-try-it","layout":{"type":"constrained"}} --><div class="wp-block-group kn-try-it"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Your turn</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Try the complete example with your own content. What changed, and what would you improve next?</p><!-- /wp:paragraph --></div><!-- /wp:group --></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
