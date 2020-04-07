import React from 'react';
import {Link, withRouter} from 'react-router-dom';
import {signout, isAuthenticated} from '../auth';
// import {products} from '../products';

const isActive = (history,path) => {
    if(history.location.pathname === path){
        return {color:"#ff9900"}
    }
    else{
        return {color:"#ffffff"}
    }
};

const Menu = ({history}) => ( // const Menu = (props.history) => (
    <div>
        <ul className="nav nav-tabs bg-primary">
          <li className="nav-item">
            <Link className="nav-link" style={isActive(history,"/")} to="/">Home</Link>
          </li>

          {!isAuthenticated() && (
            <>
              <li className="nav-item">
                <Link className="nav-link" style={isActive(history,"/signin")} to="/signin">Sign In</Link>
              </li>
              <li className="nav-item">
                <Link className="nav-link" style={isActive(history,"/signup")} to="/signup">Sign Up</Link>
              </li>
            </>
          )}

          {isAuthenticated() && (
            <>
                <li className="nav-item">
                    <a className="nav-link" style={isActive(history,"/signup"),{cursor:"pointer",color:"#fff"}} onClick={() => signout(() => history.push('/'))}>Sign Out</a>
                </li>

                <li className="nav-item">
                    <a className="nav-link">{isAuthenticated().user.name}</a>
                </li>

                <li className="nav-item">
                    <Link className="nav-link" style={isActive(history,"/products")} to="/products/listing">Products Listing</Link>
                </li>

                <li className="nav-item">
                    <Link className="nav-link" style={isActive(history,"/add_product")} to="/product/add">Add Product</Link>
                </li>

            </>
          )}


        </ul>
    </div>
)

export default withRouter(Menu);
