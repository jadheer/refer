const mongoose = require('mongoose');
const Schema   = mongoose.Schema;

const productSchema  = new Schema({
    product_name:{
        type:String,
        trim:true,
        required:true
    },
    product_code:{
        type:String,
        trim:true,
        required:true
    },
    created:{
        type:Date,
        default:Date.now
    },
    updated:{
        type:Date
    }
});

module.exports = mongoose.model('Product',productSchema);
