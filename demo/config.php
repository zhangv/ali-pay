<?php
return $cfg = [
	'app_id' => '2018071260534977',
	'sign_type' => 'RSA2',
	'input_charset' => 'UTF-8',
	'version' => '1.0',
	'format' => 'json',
	'notify_url' => "https://www.biasura.com/pay/alipaynotify/", //服务器异步通知页面路径 //需http://格式的完整路径，不能加?id=123这类自定义参数
	'return_url' => "https://www.biasura.com/pay/alipayreturn/",//页面跳转同步通知页面路径//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
	'private_key'=> 'MIIEowIBAAKCAQEA49vPICeZVK2QOzHuXwMe3Nfu6SyV6xoXFCe2DwTK89V6aJeC/YpcvKXnXZtdmR6aIiqIw9a4VwstAYYixe9vM0Rv+doUYzyMZOYpFHoAPFPEWGim+MP0aH5obANrV3kOHkv9smmINi83k+qyLdMHspp7qWtzw9zjqzolpMFkv2oOA3y7bXsWAsvSI62ZTZiNLngX9pqTaDXrhcQBMax4fYhKRrIS03c7+2G8EzQ7jbsELgjG/zwDWjMzSgRK3eQctMpo0O+Rh4jjkSJImiqG8OB/SBzcK8VwggOzaGJ6hfkcr5nMfXrARcdEVqwq97ifRHORthF3JPbXkv3TRERkpQIDAQABAoIBABYhOguYbPbhaHmnTsxhhDTUr/prfx/3R7iIZtEsmP13hUz1Mh6nunwD7OWVelCtvTCGSwQiLYMerb9RJL1ulLE4+1sbyBEfR09hXyoC81TomdAwUc8lUO55IHElH969/hYJMVmLkFQa39341FdJAJ1jDZGVwweJw37UxeUrdzpzxnTFBFuOeo83jTMy2g+bVXcL9UtsWQ5sJir0+yJGEt29dfTmuLJaahX37iZ62zje+NAE4ic/t3RyGbjCPY0IyJS7KEq8ad4l12wQCT1DVMGknp5gFoyLCInv1lBvM/f90eROkJmvUsN7An5G3QTpLssqAH4v+Yu/cmST5Vex6kECgYEA/IciKDa2FQT+H9nNrgvOCX1KyzzNjmW+7FfM712DUsG/7x4zf/2rgRCsEC8k3k+3m+0YoC+1tllp99J0bphBXamnHfGvTs8pJQMiCEriG4EYdSmRWeUf9z0aGb6yL8QIad+Nv3rvYPypPDC4zU0Ea3aymwSZZ6u7bLGYlCSfLtUCgYEA5v3X0JtTdVHb1kukbFg3ygWATMp3Xztyv/1fmP9t1mMVoX0CbkWu6j2FmpRsmybWvFBQeLFyAGrxxGvwngAfSF+7HzKO8mDd+MwKvIl/mcVlAeomcG7iFPY6JpUn2YfUtcirH9zxHFPI4yJlnm4VljU/eB0J3lX/7TEuJgCqZpECgYBm9TGX87aJG2AA3GxfabC3bb3w3vlv9UvCUIndjeLc+uGmPEoDBQnHtWRxtMbzXM0fZauEo+8SSaJKyCNwc+MyrNjV/JPdLdk4ne5iMyW80QWHv0rju+cshlz94iIEF4jWoa7JQvYWNO1K/RJboesLZXmselORURM5Xa83tzavgQKBgQCX2MMohRMmCnvjsbIS7yMtkNQ4ptg2KFRU1XEkmLVRu60ajZ0tdG152pubdHq4u51qCbn8vVld4O+x2etBUn8+CoBuD8RcnUUOKsPcEN9q7JJr4csHGj1Q3lR3zJF3tI0mOxYTSiDOKF3kwlXfAir6pWJlyWEVYZ9uA2h83u8loQKBgE+ekvOZyI+hVEx1u8kOu4VeqQwTuzSnpvtgTv0gLxa8MbqC/CPhTUqnymGLnMMlO4pRbW3a4tkQMyM08Yd4K0k+MLQ+x5TiSZo15vIrzSr86rMAY+3FLsWwMQjUKtfEpxfnARpLG3h5/iu8SuYqqmRYiigMnJwbWTJYc4GsUZoR',
	'public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA49vPICeZVK2QOzHuXwMe3Nfu6SyV6xoXFCe2DwTK89V6aJeC/YpcvKXnXZtdmR6aIiqIw9a4VwstAYYixe9vM0Rv+doUYzyMZOYpFHoAPFPEWGim+MP0aH5obANrV3kOHkv9smmINi83k+qyLdMHspp7qWtzw9zjqzolpMFkv2oOA3y7bXsWAsvSI62ZTZiNLngX9pqTaDXrhcQBMax4fYhKRrIS03c7+2G8EzQ7jbsELgjG/zwDWjMzSgRK3eQctMpo0O+Rh4jjkSJImiqG8OB/SBzcK8VwggOzaGJ6hfkcr5nMfXrARcdEVqwq97ifRHORthF3JPbXkv3TRERkpQIDAQAB',
	'alipay_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgfwXljQqqOYYPXQakANbOLI0ko4Q5Q/dFFNSgahdHnJ6YTuPtg3GlEJTQBA30Ky2rfPtFiNAPWBqDjTdXEVx2msj872+tCzy7nelpzQnuyYiKpPxTzmSyFMFarY3WVXy6KlHauozeKVFEIWPXjaQ+OaVgxftrmQGGHo6lh7IMKiyY4dp7952C4ltipUOac+RoWddzP4NoBUN4JrmMKE9LpwUL4a9VY8LoAAOMcQqmAJxmclJ1d2XRraW+V8HQi1ABEzzzWSk5NHcVKxlnb8fjxOp3HdXsdASUOb/tlAMgx3QY+UazJbeH7mLrCB4qwcGkEYiqm95Rm4G9jKZsVNSQQIDAQAB'
];