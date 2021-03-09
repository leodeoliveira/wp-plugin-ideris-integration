<?php

/**
 * Plugin Name: Ideris Integration
 * Plugin URI: mailto:leodeoliveira.info@gmail.com
 * Description: Este plugin integra os produtos do Woocomerce a API da plataforma Ideris
 * Version: 1.0
 * Author: Léo de Oliveira 
 * Author URI: github.com/leodeoliveira
 **/

add_action('woocommerce_update_product', 'productPublished');

function productPublished($product_id) {

    $authToken = authIderisAPI()["body"];
    $authToken = str_replace('"', '', $authToken);
    
    $product = wc_get_product($product_id);
    $productObj = prepareProductObj($product);
    $response = productExists($productObj['sku'], $authToken) ? updateIderisProduct($productObj, $authToken) : addIderisProduct($productObj, $authToken);
    writeLog($response);

    remove_action('woocommerce_update_product', 'productPublished');
}

function productExists($sku, $authToken) {
    $response = getIderisProductBySku($sku, $authToken);
    return $response['response']['code'] == 200;
}

function getIderisProductBySku($sku, $authToken) {

    $url = 'http://api.ideris.com.br/Produto?sku='.$sku;
    $arguments = array(
        'method' => 'GET',
        'headers' => array(
            'Content-Type' => "application/json",
            'Authorization' => $authToken,
        ),
    );

   return wp_remote_post($url, $arguments);
}

function addIderisProduct($productObj, $authToken) {

    $url = 'http://api.ideris.com.br/Produto';
    $arguments = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => "application/json",
            'Authorization' => $authToken,
        ),
        'body' => json_encode($productObj),
    );

   return wp_remote_post($url, $arguments);
}

function updateIderisProduct($productObj, $authToken) {

    $url = 'http://api.ideris.com.br/Produto';
    $arguments = array(
        'method' => 'PUT',
        'headers' => array(
            'Content-Type' => "application/json",
            'Authorization' => trim($authToken, '"'),
        ),
        'body' => json_encode($productObj),
    );

   return wp_remote_post($url, $arguments);
}

function authIderisAPI() {
    $auth = array(
        "login_token" => "cc8e6278513577311f50cfdf4b9659005ce407971020e62ee679e7e309a89d4c3b4548c2bd919582629f7dcd85aace7b"
    );

    $url = 'http://api.ideris.com.br/Login';
    $arguments = array(
        'method' => 'POST',
        'Content-Type' => 'application/json',
        'body' => $auth
    );

   return wp_remote_post($url, $arguments);
}

function prepareProductObj($product) {
   
    $productObj = array(
        "sku"=> $product->get_sku(), //Código SKU do produto  => string, obrigatório
        "titulo" => $product->get_name(), //Tíulo / Nome do produto  => string, obrigatório
        "descricaoLonga" => $product->get_description(), //Descrição longa do produto  => string, obrigatório
        //"categoriaIdIderis" => $product->get_category_ids(), //ID da categoria no Ideris  => int, obrigatório
        "categoriaIdIderis" => 1, //ID da categoria no Ideris  => int, obrigatório
        "subCategoriaIdIderis" => 1, //ID da sub categoria no Ideris  => int, obrigatório
        "marcaIdIderis" => 1, //ID da marca no Ideris  => int, obrigatório
        "departamentoIdIderis" => 8, //ID do departamento no Ideris  => int, obrigatório
        "ncmId" => null, //ID do NCM no Ideris  => int, nullable
        "produtoOrigemId" => null, //ID da origem do produto no Ideris  => int, nullable
        "modelo" => $product->get_categories(), //Modelo do produto  => string
        "garantia" => null, //Garantia do produto  => string
        "peso" => floatval($product->get_weight()), //Peso do produto (em gramas)  => decimal, nullable
        "comprimento" => floatval($product->get_length()), //Comprimento do produto (em centímetros)  => decimal, nullable
        "largura" => floatval($product->get_width()), //Largura do produto (em centímetros)  => decimal, nullable
        "altura" => floatval($product->get_height()), //Altura do produto (em centímetros)  => decimal, nullable
        "pesoEmbalagem" => null, //Peso do produto embalado (em quilos)  => decimal, nullable
        "comprimentoEmbalagem" => null, //Comprimento do produto embalado (em metros)  => decimal, nullable
        "larguraEmbalagem" => null, //Largura do produto embalado (em metros)  => decimal, nullable
        "alturaEmbalagem" => null, //Altura do produto embalado (em metros)  => decimal, nullable
        "cest" => null, //Código CEST do produto  => string
        "ean" => null, //Código EAN do produto  => string
        "valorCusto" => floatval($product->get_regular_price()), //Valor de custo do produto  => decimal, obrigatório
        "valorVenda" => floatval($product->get_price()), //Valor de venda do produto  => decimal, obrigatório
      
        "quantidadeEstoquePrincipal" => $product->get_stock_quantity(), //Quantidade de estoque principal do produto. Somente preencher quando for produto simples.  => int, nullable
        "Variacao" => array(),
        "Imagem" => array()
    );

    $images = array();
    foreach($product->get_gallery_image_ids() as $attachment_id) 
    {
        $images[] = array (
            "urlImagem" => wp_get_attachment_url($attachment_id)
        );
    }

    foreach($product->get_children() as $variationId) {
        $productVariation = wc_get_product($variationId);
        $variationImageId = $productVariation->get_image_id();

        $productObj["Variacao"][] = array(
            "skuVariacao" => $variationId, 
            "quantidadeVariacao" => $productVariation->get_stock_quantity(),
            "nomeAtributo" => "tamanho", 
            "valorAtributo" => $productVariation->get_attribute('tamanho'),
            "Imagem" => $images
        );
    }

    return $productObj;
}

function writeLog($message) { 
    if(is_array($message)) { 
        $message = json_encode($message); 
    } 
    $product_log = get_stylesheet_directory() . '/product.json';
    $file = fopen($product_log, 'a');

    echo fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message); 
    fclose($file); 
}






