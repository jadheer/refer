const express  = require('express');
const app = express();
const { signup,signin,signout } = require('../controllers/auth');
const {userSignupValidator} = require('../validator');
const { userById } = require('../controllers/user');

const router = express.Router();

router.post('/signup',userSignupValidator,signup);
router.post('/signin',signin);
router.get('/signout',signout);

// Any route containing :userId, our app will first execute userById method
router.param('userId',userById);

module.exports = router;
