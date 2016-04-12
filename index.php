<?php
  session_start();
  $levels_list = array('main', 1, 2 ,3);
  $offs = isset($_REQUEST['offs']) ? intval($_REQUEST['offs']) : 0;
  $rpp = 30;

  $db = new PDO('mysql:dbname=comments;host=127.0.0.1', 'root', '');
  $db->query('SET NAMES utf8');

  if (!empty($_POST))
  {
	$text = '';
	$who = '';
	$parent_id = 0;
	if (!empty($_POST['comment_text']))
	{
	  $text = trim($_POST['comment_text']);
	}
	if (!empty($_POST['comment_name']))
	{
	  $who = trim($_POST['comment_name']);
	}
	if (!empty($_POST['comment_parent_id']))
	{
	  $parent_id = explode('-', $_POST['comment_parent_id']);
	}
	else
	{
	  $parent_id = array();
	}

	if (!empty($text) && !empty($who))
	{
	  $order_level = array_fill_keys($levels_list, 0);
	  $add_to_level = 0;
	  foreach ($parent_id as $i => $pid)
	  {
		$add_to_level = $i;
		if (!empty($pid))
		{
		  $order_level[$levels_list[$i]] = $pid;		  
		}
		else
		{
		  break;
		}
	  }
	  $add_to_level = empty($parent_id) ? $levels_list[0] : (count($levels_list) > $i + 1 ? $levels_list[$i + 1] : 3);

	  $query = $db->prepare('INSERT INTO comments (text, who_comment, order_level_main, order_level_1, order_level_2, order_level_3) VALUES (:text, :who, :order_main, :order_1, :order_2, :order_3)', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	  if ($query->execute(array(':text' => $text, ':who' => $who, ':order_main' => $order_level['main'], ':order_1' => $order_level[1], ':order_2' => $order_level[2], ':order_3' => $order_level[3])))
	  {

		$insert_id = $db->lastInsertId();
		$query = $db->prepare('UPDATE comments SET order_level_'.$add_to_level.' = :id WHERE id = :id', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$query->execute(array(':id' => $insert_id));
		$flash_message = '<p class="bg-info">Комментарий добавлен</p>';
	  }
	  else
	  {
		$flash_message = '<p class="bg-warning">Комментарий не добавлен</p>';
	  }
	}
    else
	{
		$flash_message = '<p class="bg-warning">Комментарий не добавлен</p>';
	}
	if (!empty($flash_message))
	{
	  $_SESSION['flash_message'] = $flash_message;
	}
	header('Location: /'.($offs > 0 ? '?offs='.$offs : ''));
	die();
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comments</title>

    <!-- Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="all.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <h1>Задание</h1>

<p>Есть главная ветка и три вложенных уровня.
Главная должна показываться в обратном порядке, ответы на комментарий первого уровня - в прямом порядке.
На странице по 30 комментариев, считаются все комментарии, включая ответы.
То есть на следующую страницу должны переноситься и ответы.</p>

<p class="bg-warning"><b>Уточнение:</b> количество уровней жестко ограничено тремя от главной</p>


<h2>Нужно:</h2>
<ul>
<li>предложить структуру базы
<li>запросы
<li>код пагинатора
</ul>

<br><br>
<h2>Комментарии</h2>

<?php

  if (!empty($_SESSION['flash_message']))
  {
	echo $_SESSION['flash_message'];
	unset($_SESSION['flash_message']);
  }


  $result = $db->query('SELECT SQL_CALC_FOUND_ROWS id,  text, who_comment, add_date, order_level_main,order_level_1, order_level_2, order_level_3 FROM comments ORDER BY order_level_main DESC, order_level_1 ASC, order_level_2 ASC, order_level_3 ASC LIMIT '.($rpp * $offs).','.$rpp);
  if ($result)
  {
	$row_count = $db->query('SELECT FOUND_ROWS()');
	$row_count = $row_count->fetchColumn();

	foreach ($result as $rec)
	{
	  $deep = 0;
	  $comment_key = array();

	  foreach ($levels_list as $level)
	  {
		if (!empty($rec['order_level_'.$level]))
		{
		  $comment_key[] = $rec['order_level_'.$level];		  
		  $deep++;
		}
		else
		{
		  break;
		}
	  }
	  $comment_key = implode('-', $comment_key);
?>
	<a name="cm<?php echo $comment_key;?>"></a>
	<blockquote class="comment comment-deep-<?php echo $deep;?>" id="comment-<?php echo $comment_key;?>">
	  <p>
<?php
	echo $rec['text'];
?>
	  </p>
	  <footer>
		  <?php echo $rec['who_comment'];?>
		  <?php echo date('d.m.Y H:i:s', strtotime($rec['add_date'])); ?>
		  <?php if ($deep < 4) { ?><a href="#cm<?php echo $comment_key;?>">ответить</a><?php } ?>
	  </footer>
	</blockquote>
<?php
	}
?>
<p class="bg-info">
<?php
	$page_count = ceil($row_count / $rpp);
	for ($i = 0; $i < $page_count; $i++)
	{

	  if ($offs == $i)
	  {
		echo $i + 1;
	  }
	  else
	  {
?>
	<a href="/?offs=<?php echo $i;?>"><?php echo $i + 1;?></a>
<?php
	  }	  
	}
?>
</p>
<?php
  }
  
?>
	<form method="post" id="post-form">
	<div class="form-group">
  	  <label for="comment_text">Текст комментария</label>
	  <textarea id="comment_text" name="comment_text" class="form-control" ></textarea>
	</div>

	<div class="form-group">
  	  <label for="comment_name">Как вас зовут</label>
	  <input type="text" id="comment_name" name="comment_name" class="form-control" value="">
    </div>

	  <button type="submit" class="btn btn-success">Добавить</button>
	  <input type="hidden" id="comment_parent_id" name="comment_parent_id" value="0">
	  <input type="hidden" name="offs" value="<?php echo $offs;?>">
	</form>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
	<script>
	  $(document).ready(function() {
		$(".comment footer a").click(function() {		  
		  var commentContainer = $(this).parent().parent();
		  var parentId = commentContainer.attr("id").replace("comment-", "");
		  var form = $("#post-form-parent");
		  if (!form || form.length == 0)
		  {
			form = $("#post-form").clone();
			form.attr("id", "post-form-parent");
		  }
		  form[0].reset();
		  $("#comment_parent_id", form).val(parentId);
		  commentContainer.append(form);
		});
	  });
	</script>
  </body>
</html>