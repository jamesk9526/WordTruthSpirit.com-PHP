<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
requireAdmin();
require ROOT_PATH . '/includes/products.php';

if (!productsAvailable()) { header('Location:' . url('admin/products.php')); exit; }
$db = database();
$id = (int) ($_GET['id'] ?? 0);
$product = null;
if ($id) {
    $statement = $db->prepare('SELECT * FROM wts_products WHERE id=?');
    $statement->execute([$id]);
    $product = $statement->fetch();
    if (!$product) { http_response_code(404); exit('Product not found.'); }
}
$product = array_merge(productDefaults(), $product ?: []);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $mode = in_array($_POST['pricing_mode'] ?? '', ['fixed','contribution'], true) ? (string)$_POST['pricing_mode'] : 'fixed';
    $maximum = trim((string) ($_POST['maximum_amount'] ?? ''));
    $values = [
        'slug'=>trim((string)($_POST['slug']??'')), 'name'=>trim((string)($_POST['name']??'')),
        'short_description'=>trim((string)($_POST['short_description']??'')), 'description'=>trim((string)($_POST['description']??'')),
        'image_url'=>trim((string)($_POST['image_url']??'')), 'badge'=>trim((string)($_POST['badge']??'')),
        'pricing_mode'=>$mode, 'price'=>number_format(max(0,(float)($_POST['price']??0)),2,'.',''),
        'suggested_amounts'=>trim((string)($_POST['suggested_amounts']??'')),
        'minimum_amount'=>number_format(max(.01,(float)($_POST['minimum_amount']??1)),2,'.',''),
        'maximum_amount'=>$maximum===''?null:number_format(max(.01,(float)$maximum),2,'.',''),
        'allow_custom_amount'=>isset($_POST['allow_custom_amount'])?1:0,
        'button_label'=>trim((string)($_POST['button_label']??'Continue with PayPal')),
        'fulfillment_note'=>trim((string)($_POST['fulfillment_note']??'')),
        'display_order'=>max(0,(int)($_POST['display_order']??10)),
        'status'=>in_array($_POST['status']??'', ['draft','published','archived'],true)?(string)$_POST['status']:'draft',
    ];
    if (isset($_FILES['image_upload']) && ($_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['image_upload'];
        if ($upload['error'] !== UPLOAD_ERR_OK) $error = 'The product image upload did not complete.';
        elseif ((int)$upload['size'] > 5 * 1024 * 1024) $error = 'Product images must be 5 MB or smaller.';
        else {
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
            $mime = $finfo ? finfo_file($finfo, (string)$upload['tmp_name']) : (function_exists('mime_content_type') ? mime_content_type((string)$upload['tmp_name']) : false);
            if ($finfo) finfo_close($finfo);
            $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (!isset($extensions[$mime])) $error = 'Upload a JPG, PNG, WebP, or GIF image.';
            else {
                $directory = ROOT_PATH . '/assets/uploads/products';
                if (!is_dir($directory) && !mkdir($directory, 0755, true)) $error = 'The product upload folder could not be created.';
                else { $filename='product-'.date('Ymd').'-'.bin2hex(random_bytes(5)).'.'.$extensions[$mime]; if (move_uploaded_file((string)$upload['tmp_name'],$directory.'/'.$filename)) $values['image_url']='assets/uploads/products/'.$filename; else $error='The uploaded product image could not be saved.'; }
            }
        }
    }
    if ($values['name']==='' || !preg_match('/^[a-z0-9-]+$/',$values['slug'])) $error='Enter a name and a lowercase URL slug using letters, numbers, and hyphens.';
    elseif ($values['image_url']!=='' && !preg_match('#^https://#i',$values['image_url']) && !preg_match('#^assets/[a-z0-9_./-]+$#i',$values['image_url'])) $error='Use an HTTPS image URL or a site path beginning with assets/.';
    elseif ($mode==='fixed' && (float)$values['price']<=0) $error='Fixed-price products need a price greater than zero.';
    elseif ($mode==='contribution' && productSuggestedAmounts($values)===[] && !$values['allow_custom_amount']) $error='Add at least one valid suggested amount or allow a custom amount.';
    elseif ($values['maximum_amount']!==null && (float)$values['maximum_amount']<(float)$values['minimum_amount']) $error='The maximum amount must be greater than the minimum.';
    elseif ($values['button_label']==='') $error='Enter checkout button text.';
    if (!$error) {
        try {
            if ($id) {
                $values['id']=$id;
                $sql='UPDATE wts_products SET slug=:slug,name=:name,short_description=:short_description,description=:description,image_url=:image_url,badge=:badge,pricing_mode=:pricing_mode,price=:price,suggested_amounts=:suggested_amounts,minimum_amount=:minimum_amount,maximum_amount=:maximum_amount,allow_custom_amount=:allow_custom_amount,button_label=:button_label,fulfillment_note=:fulfillment_note,display_order=:display_order,status=:status WHERE id=:id';
            } else {
                $sql='INSERT INTO wts_products (slug,name,short_description,description,image_url,badge,pricing_mode,price,suggested_amounts,minimum_amount,maximum_amount,allow_custom_amount,button_label,fulfillment_note,display_order,status) VALUES (:slug,:name,:short_description,:description,:image_url,:badge,:pricing_mode,:price,:suggested_amounts,:minimum_amount,:maximum_amount,:allow_custom_amount,:button_label,:fulfillment_note,:display_order,:status)';
            }
            $db->prepare($sql)->execute($values);
            header('Location:' . url('admin/products.php?saved=1')); exit;
        } catch (PDOException $exception) { $error='Unable to save. Make sure the URL slug is unique.'; }
    }
    $product=array_merge($product,$values);
}

$adminTitle=$id?'Edit product':'New product'; $currentAdminPage='products'; require __DIR__.'/_header.php';
?>
<header class="admin-title admin-title-actions"><div><p class="kicker">Catalog editor</p><h1><?=e($adminTitle)?></h1><p>Define the public presentation, pricing choice, and PayPal handoff for this item.</p></div><a class="button button-outline" href="<?=url('admin/products.php')?>">Back to products</a></header>
<?php if($error):?><p class="notice error"><?=e($error)?></p><?php endif;?>
<form method="post" enctype="multipart/form-data" class="product-editor-grid" data-product-editor><input type="hidden" name="csrf" value="<?=csrfToken()?>">
  <section class="admin-panel admin-form"><p class="kicker">Public listing</p><h2>Product details</h2><label>Name<input name="name" value="<?=e($product['name'])?>" required data-product-name></label><label>URL slug<input name="slug" value="<?=e($product['slug'])?>" placeholder="support-a-new-resource" required data-product-slug></label><label>Short description<textarea name="short_description" rows="3" maxlength="500"><?=e($product['short_description'])?></textarea></label><label>Full description<textarea name="description" rows="8"><?=e($product['description'])?></textarea></label><div class="admin-form-grid"><label>Image URL or site path<input name="image_url" value="<?=e($product['image_url'])?>" placeholder="assets/images/resource.jpg"><small>Optional when uploading a new image below.</small></label><label>Upload product photo<input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF; up to 5 MB. Upload replaces the image URL.</small></label><label>Badge<input name="badge" maxlength="80" value="<?=e($product['badge'])?>" placeholder="Most helpful"></label></div><label>Fulfillment / impact note<input name="fulfillment_note" maxlength="500" value="<?=e($product['fulfillment_note'])?>" placeholder="How the item is delivered or what this gift supports"></label></section>
  <aside class="admin-panel admin-form product-pricing-card"><p class="kicker">Checkout</p><h2>Pricing &amp; publishing</h2><label>Pricing model<select name="pricing_mode" data-pricing-mode><option value="fixed" <?=$product['pricing_mode']==='fixed'?'selected':''?>>Fixed price</option><option value="contribution" <?=$product['pricing_mode']==='contribution'?'selected':''?>>Suggested / custom amount</option></select></label><div data-fixed-pricing><label>Price<input type="number" name="price" min="0.01" step="0.01" value="<?=e((string)$product['price'])?>"></label></div><div data-contribution-pricing><label>Suggested amounts<input name="suggested_amounts" value="<?=e((string)$product['suggested_amounts'])?>" placeholder="10, 25, 50, 100"><small>Separate amounts with commas.</small></label><div class="admin-form-grid"><label>Minimum<input type="number" name="minimum_amount" min="0.01" step="0.01" value="<?=e((string)$product['minimum_amount'])?>"></label><label>Maximum (optional)<input type="number" name="maximum_amount" min="0.01" step="0.01" value="<?=e((string)$product['maximum_amount'])?>"></label></div><label class="toggle-label"><input type="checkbox" name="allow_custom_amount" value="1" <?=!empty($product['allow_custom_amount'])?'checked':''?>> Allow visitors to enter another amount</label></div><label>Checkout button<input name="button_label" value="<?=e($product['button_label'])?>" required></label><div class="admin-form-grid"><label>Display order<input type="number" name="display_order" min="0" value="<?=(int)$product['display_order']?>"></label><label>Status<select name="status"><?php foreach(['draft','published','archived'] as $status):?><option value="<?=$status?>" <?=$product['status']===$status?'selected':''?>><?=ucfirst($status)?></option><?php endforeach;?></select></label></div><div class="product-paypal-note"><strong>PayPal handoff</strong><p>Configure the merchant email and currency under Site controls. This site never receives card details.</p><a href="<?=url('admin/settings.php#paypal-settings')?>">Open PayPal settings →</a></div><button class="button button-primary" type="submit">Save product</button></aside>
</form>
<?php require __DIR__.'/_footer.php'; ?>
