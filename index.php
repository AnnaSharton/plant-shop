<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог Растений</title>
    <style>
    
        html, body {background: rgb(245, 245, 245); font-size: 18px;}
        ul {list-style-type: none; }
        .left-menu {min-width: 300px;}
        a {color:rgb(7, 10, 8); text-decoration: none;}
        a:hover {color:rgb(5, 138, 45);}
        .container {display: flex; gap: 30px; padding: 0 20px;} 
        .right-side {margin-left: 50px; flex-grow: 1;} 
        .row-products {display: flex; flex-wrap: wrap;}
        .block-product {flex: 0 0 27%; margin: 0 15px 15px 0; padding: 10px; background: rgb(215, 233, 215);}
        .buttons {display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;}
        button {background: rgb(215, 233, 215); border: 1px solid gray; border-radius: 2px; padding: 5px 15px;}  
        button:hover {color:rgb(5, 138, 45);}
        .img-catalog {width: 150px; height: 150px; background-size: cover; border: 1px solid rgb(70, 70, 70);}
        span, .bread {color:rgb(5, 138, 45);}
        .bread:hover {color:rgb(7, 10, 8);} 
        .bread::after {content: ' >';}
        .description {font-weight: 700; margin-top: 15px; font-size: 19px;}
        .full-description {text-align: center;}
        .catalog {min-height: 750px;}
        .counter {display: flex; justify-content: center;}
        .pagination li {margin-right: 15px; display:inline-block; font-weight: 700;}
        .pagination {margin-top: 20px;} 
        </style>
</head>
<body>  
    <div class="container">
    <?php   
    error_reporting(E_ALL);
    mb_internal_encoding("UTF-8");

   //$sort будет возвращать ссылку с sort get если в левом меню установлна сортировка категорий
    $sort = isset($_GET['sort']) ? '&sort='.$_GET['sort'] : '';  
    //ссылка если выбрана категория, чтобы не сбрасывалась при переходе по страницам
    $cat = isset($_GET['category']) ? '&category='.$_GET['category'] : '';    
    //чтобы оставаться в карточке товара и одновременно сортировать в меню слева
    $card = isset($_GET['product-card']) ? '&product-card='.$_GET['product-card'] : '';  
    //чтобы пользователь выйдя из карточки товара вернулся на номер страницы, на которой остановился 
    $pageNum = isset($_GET['page']) ? '&page='.$_GET['page'] : ''; 
    
try {
    $db = new SQLite3('shopdb.sqlite');

    $categories = $db->query('SELECT category, count(product) FROM `categories` LEFT JOIN `plants` ON categories.id = plants.category_id GROUP BY category');
    $categoriesCount=[]; //создаю массив в который заношу только элементы с ключами "название категории [category]" и "кол-во товаров (count(product))" в этих категориях

    while ($row = $categories->fetchArray(1)) { //возвращаю Associated Array, указываю 1 для sqlite3
        $categoriesCount[]=$row;
    }

    if (isset($_GET['sort'])) {
        if ($_GET['sort']=='amount') { //сортирую если нажата submit
        // по количеству товара
            usort($categoriesCount, function ($a, $b) { // с помощью анонимной ф-ии
                return $b['count(product)'] - $a['count(product)'];
            });
        } else {
            sort($categoriesCount); //возвращаю сортировку по алфавиту по умолчанию, если нажата submit
        }
    }
   
if (isset($_GET['category'])) {  //выбираю массивы в зависимости есть ли гет запрос на категорю, если нет - то выбираю в массив все товары
    $c=$_GET['category'];
    $plants = $db->query('SELECT * FROM `categories` LEFT JOIN `plants` ON plants.category_id = categories.id WHERE `category` = "'.$c.'" ');
} else { //исключаю значения  где товар = null в категории, чтобы не отображались пустые карточки товара
    $plants = $db->query('SELECT * FROM `categories`  LEFT JOIN `plants`ON plants.category_id = categories.id WHERE product NOT NULL ');
}

$products=[]; //массив будет содержать все данные о товарах
while ($row = $plants->fetchArray(1)) {
    $products[]=$row;
} 

    ?>
        <div class="left-menu">
            <h4>Категории товаров</h4>
            <?php $totalAmont=0; ?>
                    
            <?php //отображаю левое меню со списком категорий, с пом.foreach прохожу по массиву вывожу в виде таблицы элементы массива
            $arrCategAmount = [];
            foreach ($categoriesCount as $category) {  
                $arrCategAmount[$category['category']] = $category['count(product)'];//создаю массив (ключ=название категории, элемент=кол-ву товаров в категории)
                ?>
            <ul>
                <li> <!--Cсылки на категории, при нажатии на них осущ-ся отображение каталога с выбранной категорией+сохраняется фильтр меню слева -->
                    <a href="?category=<?=$category['category'].$sort?>"><?=$category['category']?> (<b><?=$category['count(product)']?></b>)</a>
                </li>
            </ul>                         
            <?php $totalAmont += $category['count(product)']; 
           
            }?>
            <br><a href='/?<?=$sort?>'><b>Все категории (<?=$totalAmont?>)</b></a>
                        
            <!--Кнопки сортировки-->
            <h5>Сортировать категории по:</h5>
            <div class="buttons">
                <a href="?sort=amount<?=$cat.$card?>"><button>Количеству</button></a>  
                <a href="?sort=abc<?=$cat.$card?>"><button>Алфавиту</button></a> 
            </div>
        </div>

        <div class="right-side">
            <?php // Cтраница с товаром

            if (isset($_GET['product-card'])) {  
                $prod=$_GET['product-card'];//заношу в переменную id выбранного товара
                $productCard = $db->query('SELECT * FROM `categories`  LEFT JOIN `plants`ON plants.category_id = categories.id WHERE plants.id = "'.$prod.'" ');
                $productDescription=[]; //заношу данные о товаре в ассоциативный массив
                
                while ($row = $productCard->fetchArray(1)) {
                    $productDescription=$row;
                 }?>

                <h1><?=$productDescription['product']?></h1> 
                
                <!--Хлебные крошки-->
                <div class="product-card">
                    <a class="bread" href="/?<?=$sort?>">Главная</a> 
                    <a class="bread" href="/?category=<?=$productDescription['category'].$sort.$pageNum?>"><?=$productDescription['category']?></a>
                    <span><?=$productDescription['product']?></span><br><br>
                                
                <!--Фото-->
                    <img width="250" style="box-shadow: 0 0 5px rgba(109, 106, 106, 0.5);" class="img-preview" onclick="changeSizeImage(this)"  src="<?=$productDescription['link']?>" alt="<?=$productDescription['product']?>">

                <!--Описание товара-->

                <div class="description">
                    <p>Артикул: <span><?=$productDescription['article']?></span></p>
                    <p>Цена: <span><?=$productDescription['price']?> &#8381;</span></p>
                    <p>Количество товара на складе: <span><?php echo $productDescription['stock_amount']==NULL ? '<span style="color:red;">Товар закончился!</span>' :  $productDescription['stock_amount']; ?> </span></p>
                    <?php if($productDescription['latin'] != NULL) { ?> <p>Латинское название: <span><?=$productDescription['latin']?></span></p> <?php } else{} ?>
                    <p>Вид товара: <span><?=$productDescription['product_kind']?></span></p>
                    <?php if($productDescription['bloom'] != NULL) { ?><p>Период цветения: <span><?=$productDescription['bloom']?></span></p> <?php } else{} ?>
                    <p>Высота: <span><?=$productDescription['height']?></span></p>
                    <?php if($productDescription['maturation'] != NULL) { ?><p>Срок созревания: <span><?=$productDescription['maturation']?></span></p> <?php } else{} ?>
                </div>   

                <div class="full-description">
                    <h2>Описание</h2>
                    <p><?=$productDescription['full_description']?></p>
                </div>
            </div>

            <?php
            } else { // Иначе вывожу каталог
            ?> 
                   
            <div class="header">
                <h2><?php //если выбрана категория, отображаю ее название:
                    echo isset($_GET['category']) ? 'Категория: <span>'.$_GET['category'].'<span>' : 'Все категории ';?> <!--заменяю на название категории если она выбрана--> 
                </h2>
                <h4><a href='/?<?=$sort?>'><b>Все категории</b></a></h4>
            </div>

            <?php  
                if ((isset($_GET['category']) && $arrCategAmount[$_GET['category']] > 0) || !isset($_GET['category'])) { //если в категории есть товары, отображаю каталог?>
            <div class="catalog"> <!--catalog--> 

                <?php     
                $perPage=9; //9 товаров на странице
                $pagesCount=ceil((count($products))/$perPage); //округляю в большую сторону число необходимых страниц
                $page=isset($_GET['page']) ? $_GET['page'] : 1; //е.передат гет, то номер страницы=get, иначе отображаю стр.№1

                $start=($page-1)*$perPage + 1; //на каждой странице каталог будет начинаться с товара № $start
                $finish=$start+$perPage-1; //на товаре № finish включительно закончится вывод товаров на странице
                $products3 = array_chunk($products, 3);  //делю массив на 3 для вывода в ряд по 3 товара 

                ?>

                <div class="row-products">

                    <?php    
                    $i=0; 
                    foreach ($products3 as $product) {
                        if ($i>=$start/3-1 && $i<=$finish/3-1) {  //с 1 по 9ый товар, с 10-го по 19-й, разбиваю вывод по три товара
                            foreach ($product as $p) { ?>
                                <div class="block-product"> 
                                    <a href="?product-card=<?=$p['id'].$sort.$pageNum?>">
                                        <div>
                                            <div class="img-catalog" style="background-image:url('../<?=$p['link']?>');">
                                            </div>
                                            <h5><?=mb_strimwidth($p['product'], 0, 22, '...')?></h5>
                                            <p>Цена: <?=$p['price']?> &#8381;</p>
                                        </div>
                                    </a>                        
                                </div>
                            <?php 
                            }
                        } $i++;         
                    }?>
                </div>

            </div> <!--catalog--> 

            <div class="counter"> <!--блок со счетчиком страниц-->
						
                <ul class="pagination">
                    <li><a href="?page=1<?=$sort.$cat?>">В начало</a></li>

                    <li class="<?php if($page <= 1){ echo 'disabled'; } ?>"> <!--если страница №1, то ссылка previous неактивна-->
                        <a href="<?php if($page <= 1){ echo '#'; } else { echo "?page=".($page - 1).$sort.$cat; } ?>"><<<</a>
                    </li>
                    <!--если страница не = первой или последней, то вывожу в списке page-1 и page+1  + номер страницы, на которой сейчас пользователь-->
                    <?php if ($page!=1) {?> <li><a href="?page=<?=$page-1?><?=$sort.$cat?>"><?=$page-1?></a></li><?php } else {} ?>

                    <li><a href="?page=<?=$page?><?=$sort.$cat?>"><?=$page?></a></li>

                    <?php if ($page!=$pagesCount) {?> <li><a href="?page=<?=$page+1?><?=$sort.$cat?>"><?=$page+1?></a></li><?php } else {} ?>

                    <li class="<?php if($page >= $pagesCount){ echo 'disabled'; } ?>">
                        <a href="<?php if($page >= $pagesCount){ echo '#'.$sort.$cat; } else { echo "?page=".($page + 1).$sort.$cat; } ?>">>>></a>
                    </li>

                    <li><a href="?page=<?=$pagesCount?><?=$sort.$cat?>">В конец</a></li>
                </ul>
			</div> <!--counter-->

<?php       } else { //иначе если в категории товара нет?>  
                    <p>К сожалению, все товары из этой категории проданы..</p>
<?php       } 
        } 
?>  
        </div><!--right-side-->

<?php 
} 
catch(PDOException $e) { 
    echo "UError #1873: Произошла ошибка в работе с базой данных...";
    exit();
}?>
    </div><!--container-->
    <script> //функция для увеличения/уменьшения картинки по клику в зависимости от ее ширины
        function changeSizeImage(im) {
            if(im.width == "250") im.width = "600";
            else im.width = "250";
            }
    </script>
</body>
</html>
