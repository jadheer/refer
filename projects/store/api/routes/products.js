const express  = require('express');
const app = express();
// const { getPosts, createPost,postsByUser,postById,isPoster,deletePost,updatePost } = require('../controllers/post');
const { createProduct,listProduct,productById,updateProduct,deleteProduct } = require('../controllers/product');
// const {createPostValidator} = require('../validator');
const { requireSignin } = require('../controllers/adminAuth');
// const { userById } = require('../controllers/user');

const router = express.Router();

// router.get('/posts',getPosts);
// router.post('/products/add',requireSignin,createProduct);
router.post('/products/add',createProduct);
router.get('/products/listing',listProduct);
router.get('/product/:productId',productById);
router.put('/product/:productId',updateProduct);
router.delete('/product/:productId',deleteProduct);
// router.get('/posts/by/:userId',requireSignin,postsByUser);
// router.delete('/post/:postId',requireSignin,isPoster,deletePost);
// router.put('/post/:postId',requireSignin,isPoster,updatePost);

// Any route containing :userId, our app will first execute userById method
// router.param('userId',userById);

// Any route containing :postId, our app will first execute postById method
// router.param('postId',postById);

module.exports = router;
