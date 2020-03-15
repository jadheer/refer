const express  = require('express');
const app = express();
const { getPosts, createPost,postsByUser,postById,isPoster,deletePost,updatePost } = require('../controllers/post');
const {createPostValidator} = require('../validator');
const { requireSignin } = require('../controllers/auth');
const { userById } = require('../controllers/user');

const router = express.Router();

router.get('/posts',getPosts);
router.post('/post/new/:userId',requireSignin,createPost,createPostValidator);
router.get('/posts/by/:userId',requireSignin,postsByUser);
router.delete('/post/:postId',requireSignin,isPoster,deletePost);
router.put('/post/:postId',requireSignin,isPoster,updatePost);

// Any route containing :userId, our app will first execute userById method
router.param('userId',userById);

// Any route containing :postId, our app will first execute postById method
router.param('postId',postById);

module.exports = router;
