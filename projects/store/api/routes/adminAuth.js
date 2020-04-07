const express  = require('express');
const app = express();
const { signin,signout } = require('../controllers/adminAuth');
const { userById } = require('../controllers/user');

const router = express.Router();

router.post('/admin/signin',signin);
router.get('/admin/signout',signout);

// Any route containing :userId, our app will first execute userById method
router.param('userId',userById);

module.exports = router;
