const Product = require('../models/product');
//const formidable = require('formidable');
const fs = require('fs');
const _ = require('lodash');

exports.postById = (req,res,next,id) => {
    Product.findById(id)
    .exec((err,product)=>{
        if(err || !product){
            return res.status(400).json({
                error:err
            });
        }
        req.product = product;
        next();
    });
};

exports.productById = (req,res) => {
    let id = req.params.productId;
    const product = Product.findById(id)
    .select("_id product_name product_code")
    .then((product)=>{
        res.json({
            product : product
        });
    })
    .catch(err=>console.log(err));
};

exports.listProduct = (req,res) => {
    const products = Product.find()
    .select("_id product_name product_code")
    .then((products)=>{
        res.json({
            products : products
        });
    })
    .catch(err=>console.log(err));
};

exports.createProduct = async(req,res) => {
    const productExists = await Product.findOne({product_code:req.body.product_code});
    if(productExists){
        return res.status(403).json({
            error : "Product code already exists!"
        });
    }
    const product = await new Product(req.body);
    await product.save();
    res.status(200).json({message:"Product created successfully"});
};

exports.postsByUser = (req,res) => {
    //console.log(req.profile);
    Post.find({postedBy:req.profile._id})
        .populate("postedBy","_id name")
        .sort("_created")
        .exec((err,posts) => {
            if(err){
                return res.status(400).json({
                    error:err
                });
            }
            res.json(posts);
        });
};


exports.isPoster = (req,res,next) => {
    let isPoster = req.post && req.auth && req.post.postedBy._id == req.auth._id;

    if(!isPoster){
        return res.status(403).json({
            error : "User is not authorized"
        });
    }
    next();
};

exports.updateProduct = (req,res) => {
    let id = req.params.productId;
    let product = req.product;
    product = _.extend(product,req.body);
    product.updated = Date.now();
    Product.findOneAndUpdate({_id:id},product)
    .then((docs)=>{
        res.json({
            message : "Product updated successfully"
        });
    })
    .catch(err=>console.log(err));
}

exports.deleteProduct = (req,res) => {
    let id = req.params.productId;
    Product.findOneAndDelete({_id:id})
    .then((docs)=>{
        res.json({
            message : "Product deleted successfully"
        });
    })
    .catch(err=>console.log(err));
};
